<x-mail::message>
# Your sign-in code

Use this code to sign in to {{ config('app.name') }}:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

The code works once and expires in {{ $minutes }} minutes. If you did not request it, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
