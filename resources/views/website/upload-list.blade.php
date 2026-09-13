@extends('website.layout')

@section('title', 'Upload School List - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 lg:py-14">
            <div class="self-start">
                <p class="text-sm font-black uppercase tracking-wide text-emerald-700">Upload school list</p>
                <h1 class="mt-3 text-[32px] font-black leading-tight text-[#07215f] sm:text-5xl">Send the list. We prepare the basket.</h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">
                    Upload a photo, PDF, Word document or spreadsheet of your child&apos;s school requirements. EduKit will review it against the master catalogue and prepare a quote for school or pickup delivery.
                </p>

                <div class="mt-8 grid gap-3 text-sm font-semibold text-slate-700 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                    <div class="rounded border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-2xl font-black text-emerald-700">1</p>
                        <p class="mt-1">Upload the list</p>
                    </div>
                    <div class="rounded border border-sky-100 bg-sky-50 p-4">
                        <p class="text-2xl font-black text-sky-700">2</p>
                        <p class="mt-1">EduKit reviews</p>
                    </div>
                    <div class="rounded border border-amber-100 bg-amber-50 p-4">
                        <p class="text-2xl font-black text-amber-700">3</p>
                        <p class="mt-1">You get a quote</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('website.upload-list.store') }}" enctype="multipart/form-data" class="rounded border border-slate-200 bg-[#f8fbff] p-5 shadow-sm sm:p-7">
                @csrf

                @if (session('status'))
                    <div class="mb-6 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-bold text-slate-700" for="parent_name">Parent or guardian name</label>
                        <input id="parent_name" name="parent_name" value="{{ old('parent_name') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('parent_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="phone">Phone number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="learner_name">Learner name</label>
                        <input id="learner_name" name="learner_name" value="{{ old('learner_name') }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('learner_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="class_level">Class or level</label>
                        <input id="class_level" name="class_level" value="{{ old('class_level') }}" placeholder="P.5, S.2, Baby class" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('class_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="delivery_preference">Delivery preference</label>
                        <select id="delivery_preference" name="delivery_preference" required data-upload-delivery-preference class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="school" @selected(old('delivery_preference', 'school') === 'school')>Deliver to school</option>
                            <option value="pickup" @selected(old('delivery_preference') === 'pickup')>Pickup from warehouse</option>
                        </select>
                        @error('delivery_preference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div data-upload-school-fields>
                        <label class="text-sm font-bold text-slate-700" for="school_id">School</label>
                        <select id="school_id" name="school_id" data-upload-school-select class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="" data-fee="0">Select school</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" data-fee="{{ $school->delivery_fee }}" data-location="{{ $school->location }}" data-district="{{ $school->district?->name }}" @selected((int) old('school_id') === $school->id)>
                                    {{ $school->name }} - {{ $school->district?->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <div class="rounded border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-950">
                            <p class="font-black">School delivery fee: <span data-upload-school-fee>UGX 0</span></p>
                            <p class="mt-1 text-xs leading-5 text-emerald-800" data-upload-school-location>Select a school to show the delivery fee. EduKit will add item pricing after reviewing the uploaded list.</p>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-slate-700" for="shopping_list">School list file</label>
                        <input id="shopping_list" name="shopping_list" type="file" required accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="mt-1 block w-full rounded border border-slate-300 bg-white p-2 text-sm text-slate-700 file:mr-4 file:rounded file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-emerald-700">
                        <p class="mt-1 text-xs text-slate-500">Accepted: image, PDF, Word or Excel. Maximum 8 MB.</p>
                        @error('shopping_list') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-slate-700" for="notes">Extra notes</label>
                        <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Mention urgent items, preferred brands, boarding requirements or delivery timing.">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button class="mt-7 w-full rounded bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700 sm:w-auto">Submit school list</button>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const uploadDeliveryPreference = document.querySelector('[data-upload-delivery-preference]');
        const uploadSchoolSelect = document.querySelector('[data-upload-school-select]');
        const uploadSchoolFields = document.querySelectorAll('[data-upload-school-fields]');
        const uploadSchoolFee = document.querySelector('[data-upload-school-fee]');
        const uploadSchoolLocation = document.querySelector('[data-upload-school-location]');
        const uploadLearnerInput = document.getElementById('learner_name');
        const uploadClassInput = document.getElementById('class_level');
        const uploadFormatUgx = (amount) => `UGX ${Number(amount || 0).toLocaleString('en-US')}`;

        const syncUploadSchoolFee = () => {
            const isSchoolDelivery = uploadDeliveryPreference?.value === 'school';
            const selectedSchool = uploadSchoolSelect?.selectedOptions?.[0];
            const fee = isSchoolDelivery ? Number(selectedSchool?.dataset.fee || 0) : 0;
            const location = selectedSchool?.dataset.location || selectedSchool?.dataset.district || '';

            uploadSchoolFields.forEach((field) => field.classList.toggle('hidden', ! isSchoolDelivery));

            if (uploadSchoolSelect) {
                uploadSchoolSelect.required = isSchoolDelivery;
            }

            if (uploadLearnerInput && uploadClassInput) {
                uploadLearnerInput.required = isSchoolDelivery;
                uploadClassInput.required = isSchoolDelivery;
            }

            uploadSchoolFee.textContent = uploadFormatUgx(fee);
            uploadSchoolLocation.textContent = isSchoolDelivery
                ? (selectedSchool?.value ? `Delivery to ${location || selectedSchool.textContent.trim()}.` : 'Select a school to show the delivery fee. EduKit will add item pricing after reviewing the uploaded list.')
                : 'Pickup from the EduKit warehouse has no delivery fee. EduKit will add item pricing after reviewing the uploaded list.';
        };

        uploadDeliveryPreference?.addEventListener('change', syncUploadSchoolFee);
        uploadSchoolSelect?.addEventListener('change', syncUploadSchoolFee);
        syncUploadSchoolFee();
    </script>
@endpush
