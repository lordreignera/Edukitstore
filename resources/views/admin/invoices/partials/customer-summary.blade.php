<dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
    <div>
        <dt class="font-semibold text-gray-500">Parent</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->parent_name }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">Phone</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->phone }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">Email</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->email ?? '-' }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">School</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->school?->name ?? $invoice->school_name ?? '-' }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">District</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->school?->district?->name ?? $invoice->district?->name ?? '-' }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">Learner</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->learner_name ?? '-' }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">Class</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->class_level ?? '-' }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">Delivery</dt>
        <dd class="mt-1 text-gray-950">{{ ucfirst($invoice->delivery_preference) }}</dd>
    </div>
    <div>
        <dt class="font-semibold text-gray-500">Location</dt>
        <dd class="mt-1 text-gray-950">{{ $invoice->delivery_location ?? '-' }}</dd>
    </div>
</dl>

<div class="mt-6 border-t border-gray-100 pt-5">
    <p class="text-sm font-semibold text-gray-500">Notes</p>
    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $invoice->notes ?: 'No extra notes.' }}</p>
</div>
