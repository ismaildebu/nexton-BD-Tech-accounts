@if($invoice->due_amount > 0 && $invoice->status !== 'paid')
<div class="mt-4 border-t pt-4">
    <button
        id="bkash-pay-btn"
        data-invoice-id="{{ $invoice->id }}"
        onclick="initiateBkashPayment()"
        class="w-full flex items-center justify-center gap-2 bg-pink-500 hover:bg-pink-600 text-white font-semibold py-3 px-6 rounded-xl transition-colors"
    >
        বিকাশে পেমেন্ট করুন (৳{{ number_format($invoice->due_amount, 2) }})
    </button>
</div>

<div id="bkash-loading" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 text-center shadow-xl">
        <div class="animate-spin w-12 h-12 border-4 border-pink-500 border-t-transparent rounded-full mx-auto mb-4"></div>
        <p class="font-medium">বিকাশ পেমেন্ট পেজে নিয়ে যাচ্ছি...</p>
    </div>
</div>

<script>
async function initiateBkashPayment() {
    const btn = document.getElementById('bkash-pay-btn');
    const loading = document.getElementById('bkash-loading');
    btn.disabled = true;
    loading.classList.remove('hidden');
    try {
        const res = await fetch(`/invoices/${btn.dataset.invoiceId}/bkash/initiate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success && data.payment_url) {
            window.location.href = data.payment_url;
        } else {
            loading.classList.add('hidden');
            btn.disabled = false;
            alert('পেমেন্ট শুরু করতে সমস্যা হয়েছে।');
        }
    } catch (e) {
        loading.classList.add('hidden');
        btn.disabled = false;
        alert('নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।');
    }
}
</script>
@endif