@if (session('status'))
    <div class="mb-4 rounded-control border border-success-100 bg-success-50 px-4 py-3 text-sm text-success-700" role="status">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-control border border-danger-100 bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-control border border-danger-100 bg-danger-50 px-4 py-3 text-sm text-danger-700" role="alert">
        <p class="font-semibold">Periksa kembali isian berikut:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
