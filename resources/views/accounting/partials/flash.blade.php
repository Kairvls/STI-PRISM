@if(session('success'))
    <div class="acc-flash acc-flash-ok fade-in">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="acc-flash acc-flash-err fade-in">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="acc-flash acc-flash-err fade-in">{{ $errors->first() }}</div>
@endif
