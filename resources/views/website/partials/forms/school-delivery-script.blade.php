<script>
    (() => {
        if (window.EduKitSchoolDeliveryBound) {
            return;
        }

        window.EduKitSchoolDeliveryBound = true;

        const formatUgx = (amount) => `UGX ${Number(amount || 0).toLocaleString('en-US')}`;

        document.querySelectorAll('[data-school-delivery]').forEach((container) => {
            const subtotal = Number(container.dataset.subtotal || 0);
            const hasEdukitItems = container.dataset.hasEdukitItems !== '0';
            const supplierFees = JSON.parse(container.dataset.supplierFees || '[]');
            const deliveryPreference = container.querySelector('[data-delivery-preference]');
            const schoolSelect = container.querySelector('[data-school-select]');
            const schoolFields = container.querySelectorAll('[data-school-fields]');
            const requiredSchoolFields = container.querySelectorAll('[data-require-school-delivery]');
            const deliveryFeeText = container.querySelector('[data-delivery-fee]');
            const grandTotalText = container.querySelector('[data-grand-total]');
            const schoolFeeText = container.querySelector('[data-school-fee-text]');
            const schoolLocationText = container.querySelector('[data-school-location-text]');

            const syncTotals = () => {
                const isSchoolDelivery = deliveryPreference?.value === 'school';
                const selectedSchool = schoolSelect?.selectedOptions?.[0];
                const schoolDistrict = selectedSchool?.dataset.district || '';
                const edukitFee = isSchoolDelivery && hasEdukitItems ? Number(selectedSchool?.dataset.fee || 0) : 0;
                const supplierFee = isSchoolDelivery && selectedSchool?.value
                    ? supplierFees.reduce((total, supplier) => total + (String(supplier.district).toLowerCase() === schoolDistrict.toLowerCase() ? Number(supplier.local_fee || 0) : Number(supplier.other_fee || 0)), 0)
                    : 0;
                const fee = edukitFee + supplierFee;
                const location = selectedSchool?.dataset.location || selectedSchool?.dataset.district || '';

                schoolFields.forEach((field) => field.classList.toggle('hidden', ! isSchoolDelivery));

                if (schoolSelect) {
                    schoolSelect.required = isSchoolDelivery;
                }

                requiredSchoolFields.forEach((field) => {
                    field.required = isSchoolDelivery;
                });

                if (deliveryFeeText) {
                    deliveryFeeText.textContent = formatUgx(fee);
                }

                if (grandTotalText) {
                    grandTotalText.textContent = formatUgx(subtotal + fee);
                }

                if (schoolFeeText) {
                    schoolFeeText.textContent = formatUgx(fee);
                }

                if (schoolLocationText) {
                    const schoolHelp = schoolLocationText.dataset.schoolHelp || 'Select a school to show the destination fee.';
                    const pickupHelp = schoolLocationText.dataset.pickupHelp || 'Pickup from the EduKit warehouse has no delivery fee.';

                    schoolLocationText.textContent = isSchoolDelivery
                        ? (selectedSchool?.value ? `Delivery to ${location || selectedSchool.textContent.trim()}.` : schoolHelp)
                        : pickupHelp;
                }
            };

            deliveryPreference?.addEventListener('change', syncTotals);
            schoolSelect?.addEventListener('change', syncTotals);
            syncTotals();
        });
    })();
</script>
