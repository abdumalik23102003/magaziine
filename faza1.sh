mkdir -p app/Policies/Concerns app/Http/Middleware app/Filament/Resources/Users/Schemas database/seeders

cat > app/Enums/UserRole.php <<'EOF'
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Customer = 'customer';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Operator = 'operator';
    case Partner = 'partner';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Customer => 'gray',
            self::Admin => 'danger',
            self::Moderator => 'warning',
            self::Operator => 'info',
            self::Partner => 'success',
        };
    }
}
EOF

cat > database/migrations/2026_11_25_100000_add_role_and_active_to_users_table.php <<'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->after('password')->index();
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        DB::table('users')->where('role', 'admin')->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
EOF

cat > database/migrations/2026_11_25_100001_create_brand_user_table.php <<'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['brand_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_user');
    }
};
EOF

cat > app/Models/User.php <<'EOF'
<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** role va is_active ataylab $fillable da yo'q: oddiy forma orqali o'zgarmasin. */
#[Fillable(['name', 'email', 'password', 'phone', 'address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'customer',
        'is_active' => true,
    ];

    /** @var array<int, int>|null */
    protected ?array $brandIdsCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    /** Admin panelga kira oladigan xodimlar. */
    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Moderator, UserRole::Operator);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'partner' => $this->hasRole(UserRole::Partner, UserRole::Admin),
            default => false,
        };
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Hamkor boshqaradigan brendlar. */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)->withTimestamps();
    }

    public function ownsBrand(?int $brandId): bool
    {
        if ($brandId === null) {
            return false;
        }

        $this->brandIdsCache ??= $this->brands()->pluck('brands.id')->map(fn ($id): int => (int) $id)->all();

        return in_array($brandId, $this->brandIdsCache, true);
    }
}
EOF

cat > app/Models/Brand.php <<'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Shu brendni boshqaradigan hamkorlar. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
EOF

cat > database/factories/UserFactory.php <<'EOF'
<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Customer,
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function moderator(): static
    {
        return $this->state(['role' => UserRole::Moderator]);
    }

    public function operator(): static
    {
        return $this->state(['role' => UserRole::Operator]);
    }

    public function partner(): static
    {
        return $this->state(['role' => UserRole::Partner]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
EOF

cat > app/Policies/Concerns/AdminOnlyByDefault.php <<'EOF'
<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Standart: faqat faol admin ruxsat oladi. Policy o'ziga kerakli metodlarni qayta yozadi
 * (klass metodi trait metodidan ustun turadi).
 */
trait AdminOnlyByDefault
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function delete(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, mixed $record = null): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
EOF

cat > app/Policies/CategoryPolicy.php <<'EOF'
<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyByDefault;

/** Kategoriyalarni faqat admin boshqaradi. */
class CategoryPolicy
{
    use AdminOnlyByDefault;
}
EOF

cat > app/Policies/UnitPolicy.php <<'EOF'
<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyByDefault;

/** O'lchov birliklarini faqat admin boshqaradi. */
class UnitPolicy
{
    use AdminOnlyByDefault;
}
EOF

cat > app/Policies/UserPolicy.php <<'EOF'
<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyByDefault;

/** Foydalanuvchilar, rollar va faollashtirish: faqat admin. */
class UserPolicy
{
    use AdminOnlyByDefault;
}
EOF

cat > app/Policies/BrandPolicy.php <<'EOF'
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyByDefault;

class BrandPolicy
{
    use AdminOnlyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Moderator, UserRole::Partner);
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->hasRole(UserRole::Moderator)
            || ($user->hasRole(UserRole::Partner) && $user->ownsBrand($brand->id));
    }

    /** Yangi brendni faqat admin ochadi; hamkor faqat o'z brendi profilini tahrirlaydi. */
    public function update(User $user, Brand $brand): bool
    {
        return $user->hasRole(UserRole::Partner) && $user->ownsBrand($brand->id);
    }
}
EOF

cat > app/Policies/ProductPolicy.php <<'EOF'
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyByDefault;

class ProductPolicy
{
    use AdminOnlyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Moderator, UserRole::Partner);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Moderator) || $this->ownsProduct($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Partner);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /** Moderatsiya (5-faza dan keyin ishlatiladi). */
    public function approve(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Moderator);
    }

    public function reject(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Moderator);
    }

    private function ownsProduct(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Partner) && $user->ownsBrand($product->brand_id);
    }
}
EOF

cat > app/Policies/OrderPolicy.php <<'EOF'
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\AdminOnlyByDefault;

/** Buyurtmalar: admin va operator (ko'rish + status o'zgartirish). */
class OrderPolicy
{
    use AdminOnlyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Operator);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasRole(UserRole::Operator);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasRole(UserRole::Operator);
    }
}
EOF

cat > app/Http/Middleware/EnsureUserIsActive.php <<'EOF'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Tizimga kirib bo'lgach faolsizlantirilgan foydalanuvchini darhol chiqarib yuboradi. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account is disabled.']);
        }

        return $next($request);
    }
}
EOF

cat > app/Filament/Resources/Users/UserResource.php <<'EOF'
<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Foydalanuvchilar, rollar va hamkor-brend biriktirish (faqat admin; UserPolicy). */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $pluralModelLabel = 'Users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
EOF

cat > app/Filament/Resources/Users/Schemas/UserForm.php <<'EOF'
<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSelf = fn (?User $record): bool => $record !== null && $record->is(auth()->user());

        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('phone')->tel()->maxLength(32),
                TextInput::make('address')->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Tahrirlashda bo\'sh qoldirilsa parol o\'zgarmaydi.'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default(UserRole::Customer->value)
                    ->required()
                    ->live()
                    ->disabled($isSelf)
                    ->helperText('O\'z rolingizni o\'zgartira olmaysiz.'),
                Select::make('brands')
                    ->relationship('brands', 'name')
                    ->multiple()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Partner, UserRole::Partner->value], true))
                    ->helperText('Hamkor faqat shu brendlarni boshqaradi.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->disabled($isSelf)
                    ->helperText('O\'chirilsa, foydalanuvchi tizimga kira olmaydi.'),
            ]);
    }
}
EOF

cat > app/Filament/Resources/Users/Tables/UsersTable.php <<'EOF'
<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->placeholder('-'),
                TextColumn::make('role')->badge()->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('orders_count')->counts('orders')->label('Orders')->sortable(),
                TextColumn::make('created_at')->label('Joined')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('role')->options(UserRole::class),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
EOF

cat > app/Filament/Resources/Users/Pages/ListUsers.php <<'EOF'
<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
EOF

cat > app/Filament/Resources/Users/Pages/CreateUser.php <<'EOF'
<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** role va is_active mass-assign emas: aniq forceFill (faqat admin bu sahifaga kira oladi). */
    protected function handleRecordCreation(array $data): Model
    {
        $guarded = Arr::only($data, ['role', 'is_active']);

        $user = new User(Arr::except($data, ['role', 'is_active']));
        $user->forceFill([
            'role' => $guarded['role'] ?? UserRole::Customer->value,
            'is_active' => $guarded['is_active'] ?? true,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
EOF

cat > app/Filament/Resources/Users/Pages/EditUser.php <<'EOF'
<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $guarded = Arr::only($data, ['role', 'is_active']);

        // O'zini pasaytirish yoki o'chirib qo'yishdan himoya (forma tomonda ham disabled).
        if ($record->is(auth()->user())) {
            $guarded = [];
        }

        $record->fill(Arr::except($data, ['role', 'is_active']));
        $record->forceFill($guarded);
        $record->save();

        return $record;
    }
}
EOF

cat > database/seeders/StaffSeeder.php <<'EOF'
<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Demo xodimlar (parol: password). Ishga tushirish: sail artisan db:seed --class=StaffSeeder */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Moderator', 'moderator@magaziine.test', 'moderator'],
            ['Operator', 'operator@magaziine.test', 'operator'],
            ['Partner', 'partner@magaziine.test', 'partner'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->fill(['name' => $name, 'password' => 'password']);
            $user->forceFill(['role' => $role, 'is_active' => true, 'email_verified_at' => now()])->save();

            if ($role === 'partner' && ($brand = Brand::query()->first())) {
                $user->brands()->syncWithoutDetaching([$brand->id]);
            }
        }
    }
}
EOF

cat > tests/Feature/RolesTest.php <<'EOF'
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_roles_open_the_admin_panel(): void
    {
        foreach (['admin', 'moderator', 'operator'] as $state) {
            $this->actingAs(User::factory()->{$state}()->create())->get('/admin')->assertOk();
        }
    }

    public function test_partner_and_customer_cannot_open_the_admin_panel(): void
    {
        foreach ([User::factory()->partner()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user)->get('/admin')->assertForbidden();
        }
    }

    public function test_inactive_admin_is_locked_out_of_the_panel(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_policy_matrix(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $operator = User::factory()->operator()->create();
        $partner = User::factory()->partner()->create();
        $customer = User::factory()->create();

        $own = Brand::factory()->create();
        $other = Brand::factory()->create();
        $partner->brands()->attach($own);

        $ownProduct = (new Product)->forceFill(['brand_id' => $own->id]);
        $otherProduct = (new Product)->forceFill(['brand_id' => $other->id]);

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('update', $otherProduct));
        $this->assertTrue($admin->can('update', new Order));

        $this->assertTrue($moderator->can('viewAny', Product::class));
        $this->assertTrue($moderator->can('approve', $otherProduct));
        $this->assertFalse($moderator->can('update', $ownProduct));
        $this->assertFalse($moderator->can('viewAny', Order::class));
        $this->assertFalse($moderator->can('viewAny', User::class));

        $this->assertTrue($operator->can('viewAny', Order::class));
        $this->assertTrue($operator->can('update', new Order));
        $this->assertFalse($operator->can('viewAny', Product::class));
        $this->assertFalse($operator->can('viewAny', User::class));

        $this->assertTrue($partner->can('create', Product::class));
        $this->assertTrue($partner->can('update', $ownProduct));
        $this->assertFalse($partner->can('update', $otherProduct));
        $this->assertTrue($partner->can('update', $own));
        $this->assertFalse($partner->can('update', $other));
        $this->assertFalse($partner->can('approve', $ownProduct));
        $this->assertFalse($partner->can('viewAny', Order::class));
        $this->assertFalse($partner->can('viewAny', User::class));

        $this->assertFalse($customer->can('viewAny', Product::class));
        $this->assertFalse($customer->can('viewAny', Order::class));
        $this->assertFalse($customer->can('create', Product::class));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'off@example.test']);

        $this->post('/login', ['email' => 'off@example.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertTrue($user->fresh()->role === UserRole::Customer);
    }

    public function test_logged_in_user_is_kicked_out_when_deactivated(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/profile')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'X',
            'email' => 'x@example.test',
            'password' => 'secret-pass-1',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::Customer, $user->fresh()->role);
    }

    public function test_only_admin_opens_the_users_admin_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/create')->assertOk();

        $this->actingAs(User::factory()->moderator()->create());
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_change_role_and_attach_brands(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create();
        $brand = Brand::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => UserRole::Partner->value, 'brands' => [$brand->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame(UserRole::Partner, $user->role);
        $this->assertTrue($user->brands->contains($brand));
    }

    public function test_admin_cannot_demote_or_disable_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['role' => UserRole::Customer->value, 'is_active' => false])
            ->call('save');

        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }
}
EOF

cat > /tmp/magaziine-phase1.pl <<'EOF'
use strict;
use warnings;

sub rep {
    my ($file, $from, $to) = @_;
    open(my $in, '<', $file) or die "cannot open $file\n";
    local $/;
    my $s = <$in>;
    close $in;
    return if index($s, $to) >= 0;
    my $i = index($s, $from);
    if ($i < 0) { warn "SKIPPED (fayl sizda boshqacha): $file\n"; return; }
    substr($s, $i, length $from) = $to;
    open(my $out, '>', $file) or die "cannot write $file\n";
    print $out $s;
    close $out;
}

rep('app/Http/Controllers/AuthController.php',
    "Auth::attempt(\$credentials, \$request->boolean('remember'))",
    "Auth::attempt([...\$credentials, 'is_active' => true], \$request->boolean('remember'))");

rep('resources/views/pages/profile.blade.php',
    '@if ($user->is_admin)',
    '@if ($user->isStaff())');

rep('app/Filament/Widgets/ShopStats.php',
    "where('is_admin', false)",
    "where('role', 'customer')");

rep('app/Filament/Widgets/ShopStats.php',
    "class ShopStats extends StatsOverviewWidget\n{\n",
    "class ShopStats extends StatsOverviewWidget\n{\n    /** Daromad va statistika faqat adminga. */\n    public static function canView(): bool\n    {\n        return (bool) auth()->user()?->isAdmin();\n    }\n\n");

rep('database/seeders/DatabaseSeeder.php',
    "'is_admin' => true,",
    "'role' => 'admin',");

my $old_admin = "        \$user = User::factory()->create();\n        \$user->forceFill(['is_admin' => true])->save();\n\n        return \$user;";
my $new_admin = "        return User::factory()->admin()->create();";
rep('tests/Feature/AdminPanelTest.php', $old_admin, $new_admin);
rep('tests/Feature/AdminShopTest.php', $old_admin, $new_admin);

rep('tests/Feature/AuthTest.php', "'is_admin' => false", "'role' => 'customer'");
rep('tests/Feature/AuthTest.php', "'is_admin' => 1,", "'role' => 'admin',");
rep('tests/Feature/AuthTest.php',
    "\$this->assertFalse((bool) User::where('email', 'sneaky\@example.test')->first()->is_admin);",
    "\$this->assertSame('customer', User::where('email', 'sneaky\@example.test')->first()->role->value);");

rep('tests/Feature/ProfileTest.php', "'is_admin' => 1,", "'role' => 'admin',");
rep('tests/Feature/ProfileTest.php',
    "\$this->assertFalse((bool) \$user->fresh()->is_admin);",
    "\$this->assertSame('customer', \$user->fresh()->role->value);");

print "patches ok\n";
EOF
perl /tmp/magaziine-phase1.pl
rm -f /tmp/magaziine-phase1.pl
