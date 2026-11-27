<?php

namespace Tests\Unit;

use App\Support\OneTimeCode;
use App\Support\TelegramLoginWidget;
use PHPUnit\Framework\TestCase;

/** Kutilgan qiymatlar PHP'dan mustaqil, Python (hashlib/hmac) bilan hisoblangan. */
class TelegramLoginWidgetTest extends TestCase
{
    private const TOKEN = '123456:TEST-bot-token';

    private function data(): array
    {
        return [
            'id' => '4242', 'first_name' => 'Ali', 'last_name' => 'Valiyev', 'username' => 'ali_v',
            'auth_date' => '1700000000', 'photo_url' => 'https://t.me/i/userpic/320/ali.jpg',
        ];
    }

    public function test_signature_matches_the_independent_vector(): void
    {
        $this->assertSame('f7a0ba69ff5a703be777fe54d6f52e1ba2a695b35a24f301921b31d745074d0d', TelegramLoginWidget::signature($this->data(), self::TOKEN));
    }

    public function test_valid_and_tampered_data(): void
    {
        $data = $this->data() + ['hash' => 'f7a0ba69ff5a703be777fe54d6f52e1ba2a695b35a24f301921b31d745074d0d'];
        $this->assertTrue(TelegramLoginWidget::isValid($data, self::TOKEN));

        $data['id'] = '1';
        $this->assertFalse(TelegramLoginWidget::isValid($data, self::TOKEN));
        $this->assertFalse(TelegramLoginWidget::isValid($this->data(), self::TOKEN), 'hash yo\'q');
        $this->assertFalse(TelegramLoginWidget::isValid($this->data() + ['hash' => 'x'], ''), 'token yo\'q');
        $this->assertFalse(TelegramLoginWidget::isValid($this->data() + ['hash' => ['a']], self::TOKEN));
    }

    public function test_one_time_code_hash_matches_the_independent_vector(): void
    {
        $this->assertSame('c67a84d72854c8cf7215eadbec01562b64f046c0197305e3800eacdaf8fa0f8b', OneTimeCode::hash('user@example.test', '123456', 'base64:test-key'));
    }

    public function test_generated_code_is_six_digits(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', OneTimeCode::generate());
        }
    }
}
