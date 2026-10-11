<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cahya Audio — Blockchain Verification Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0E1A17;
            --surface: #15241F;
            --surface-2: #1B2B25;
            --ink: #EAE7DB;
            --muted: #9CA79E;
            --border: #263A32;
            --primary: #6FBFA9;
            --accent: #D9A94D;
            --success: #2E7D5B;
            --danger: #E0825E;
        }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            padding: 32px 24px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
        }
        .brand {
            font-family: 'Lora', serif;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); }
        .sub-header { color: var(--muted); font-size: 13.5px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .stat-card .label {
            font-size: 11.5px;
            color: var(--muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .stat-card .val {
            font-size: 20px;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            margin-top: 8px;
            word-break: break-all;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .badge.online { background: rgba(46, 125, 91, 0.2); color: #6FBFA9; border: 1px solid var(--success); }
        .badge.verified { background: rgba(46, 125, 91, 0.2); color: #6FBFA9; border: 1px solid var(--success); }
        .badge.pending { background: rgba(217, 169, 77, 0.2); color: var(--accent); border: 1px solid var(--accent); }
        .badge.cancelled { background: rgba(224, 130, 94, 0.2); color: var(--danger); border: 1px solid var(--danger); }

        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }
        .table-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            font-size: 15px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            padding: 14px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        th {
            background: var(--surface-2);
            color: var(--muted);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }
        tr:hover { background: rgba(255, 255, 255, 0.02); }
        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px;
        }
        a.tx-link {
            color: var(--primary);
            text-decoration: none;
        }
        a.tx-link:hover { text-decoration: underline; }

        .pagination {
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="brand">
            <span class="dot"></span>
            Cahya Audio — Blockchain Verification Dashboard
        </div>
        <div class="sub-header">
            Audit Trail On-Chain &amp; Sinkronisasi Ganache
        </div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Node RPC Status</div>
            <div class="val">
                <span class="badge online">{{ $systemInfo['node_status'] }}</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">Block Number Terkini</div>
            <div class="val" style="color: var(--accent);">#{{ number_format($systemInfo['latest_block']) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Alamat Smart Contract</div>
            <div class="val mono" title="{{ $systemInfo['contract_address'] }}" style="font-size: 13px;">
                {{ substr($systemInfo['contract_address'], 0, 10) }}…{{ substr($systemInfo['contract_address'], -6) }}
            </div>
        </div>
        <div class="stat-card">
            <div class="label">Audit Terverifikasi</div>
            <div class="val">{{ $systemInfo['verified_audits'] }} / {{ $systemInfo['total_orders'] }} Order</div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <span>Daftar Bukti Transaksi On-Chain (Audit Trail)</span>
            <span class="sub-header">Semua bukti dicatat permanen di blockchain</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Order Code</th>
                    <th>Pelanggan &amp; Mobil</th>
                    <th>Transaction Hash (Tx)</th>
                    <th>Block</th>
                    <th>Status On-Chain</th>
                    <th>Gas Used</th>
                    <th>Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditTrails as $trail)
                    <tr>
                        <td class="mono"><strong>{{ $trail->order->order_code ?? '—' }}</strong></td>
                        <td>{{ $trail->order->customer_name ?? '—' }} <small style="color:var(--muted)">({{ $trail->order->car_model ?? '—' }})</small></td>
                        <td class="mono">
                            <span title="{{ $trail->tx_hash }}">
                                {{ substr($trail->tx_hash, 0, 10) }}…{{ substr($trail->tx_hash, -6) }}
                            </span>
                        </td>
                        <td class="mono">#{{ $trail->block_number }}</td>
                        <td>
                            @if($trail->onchain_status === 'Completed')
                                <span class="badge verified">Completed</span>
                            @elseif($trail->onchain_status === 'Cancelled')
                                <span class="badge cancelled">Cancelled</span>
                            @else
                                <span class="badge pending">{{ $trail->onchain_status }}</span>
                            @endif
                        </td>
                        <td class="mono">{{ number_format($trail->gas_used) }}</td>
                        <td>
                            @if($trail->is_verified)
                                <span class="badge verified">✓ Verified Receipt</span>
                            @else
                                <span class="badge pending">Pending Mining</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--muted); padding: 32px;">
                            Belum ada transaksi audit trail yang tercatat. Transaksi akan muncul otomatis saat pembayaran atau perubahan status terjadi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($auditTrails, 'links'))
            <div class="pagination">
                {{ $auditTrails->links() }}
            </div>
        @endif
    </div>
</div>

</body>
</html>

