<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment · Nexton Accounts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-md mx-auto px-4 py-16 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-red-600 mb-2">Payment not completed</h1>
        <p class="text-gray-600">{{ $message ?? 'Something went wrong with your payment.' }}</p>
        <p class="text-gray-500 text-sm mt-4">No money is recorded until the payment is confirmed. If you were charged, please contact support with your payment reference.</p>
    </div>
</body>
</html>
