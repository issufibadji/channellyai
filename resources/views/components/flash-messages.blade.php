@if (session('success'))
    <x-alert variant="success" class="mb-4">{{ session('success') }}</x-alert>
@endif

@if (session('error'))
    <x-alert variant="error" class="mb-4">{{ session('error') }}</x-alert>
@endif

@if (session('warning'))
    <x-alert variant="warning" class="mb-4">{{ session('warning') }}</x-alert>
@endif

@if (session('info'))
    <x-alert variant="info" class="mb-4">{{ session('info') }}</x-alert>
@endif
