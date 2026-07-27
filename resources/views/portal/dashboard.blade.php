<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Portal</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/css/styles.css') }}">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-0">{{ $party->party_name }}</h1>
            <div class="text-muted">{{ $party->party_code }}</div>
        </div>
        <form method="post" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-outline-secondary btn-sm" type="submit">Logout</button></form>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm"><div class="card-body">
                <h2 class="h6">Sales Orders</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>No</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>{{ $order->document_no }}</td>
                                <td>{{ $order->document_date?->format('d-m-Y') }}</td>
                                <td>{{ $order->status?->label() ?? $order->status }}</td>
                                <td class="text-end">{{ number_format((float) $order->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No orders yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm"><div class="card-body">
                <h2 class="h6">Invoices</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>No</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->document_no }}</td>
                                <td>{{ $invoice->document_date?->format('d-m-Y') }}</td>
                                <td>{{ $invoice->status?->label() ?? $invoice->status }}</td>
                                <td class="text-end">{{ number_format((float) $invoice->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No invoices yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>
</div>
</body>
</html>
