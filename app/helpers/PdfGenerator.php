<?php
// app/helpers/PdfGenerator.php
// Wraps Dompdf to generate PDF for bills and receipts.

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator {
    /**
     * Stream a PDF to the browser from an HTML string.
     */
    public static function fromHtml(string $html, string $filename, bool $download = true): void {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => $download ? 1 : 0]);
        exit;
    }

    /**
     * Build a self-contained HTML string for a bill/SOA, suitable for Dompdf.
     */
    public static function billHtml(array $bill, array $settings = []): string {
        $addr   = htmlspecialchars($settings['company_address'] ?? 'La Mesa, Quezon City');
        $phone  = htmlspecialchars($settings['contact_number']  ?? '');
        $name   = htmlspecialchars($bill['customer_name']       ?? '');
        $acct   = htmlspecialchars($bill['account_number']      ?? '');
        $type   = htmlspecialchars($bill['account_type']        ?? '');
        $addr2  = htmlspecialchars($bill['address']             ?? '');
        $bgy    = htmlspecialchars($bill['barangay_name']       ?? '');
        $meter  = htmlspecialchars($bill['meter_number']        ?? '');
        $billNo = htmlspecialchars($bill['bill_number']         ?? '');
        $period = date('F Y', strtotime($bill['billing_period'] ?? 'now'));
        $prev   = number_format((int)($bill['prev_reading_val'] ?? 0));
        $curr   = number_format((int)($bill['curr_reading_val'] ?? 0));
        $cons   = (int)($bill['consumption_val']  ?? 0);
        $consF  = number_format($cons);
        $minCh  = number_format((float)($bill['applied_min_rate']    ?? 0), 2);
        $rateM3 = number_format((float)($bill['applied_rate_per_m3'] ?? 0), 2);
        $sub    = number_format((float)($bill['subtotal']             ?? 0), 2);
        $prevUnpaid = (float)($bill['previous_unpaid'] ?? 0);
        $penalty    = (float)($bill['penalty_amount']  ?? 0);
        $total  = number_format((float)($bill['total_amount'] ?? 0), 2);
        $due    = date('F d, Y', strtotime($bill['due_date']  ?? 'now'));
        $status = htmlspecialchars($bill['status'] ?? '');
        $statusColor = ($status === 'Paid') ? '#15803d' : (($status === 'Overdue') ? '#b91c1c' : '#b45309');
        $phoneStr = $phone ? " | $phone" : '';

        $css = '
body{font-family:DejaVu Sans,sans-serif;font-size:11pt;color:#1e293b;margin:0;padding:28px;}
h2{margin:0;font-size:16pt;color:#0284c7;}
.sub{font-size:9pt;color:#64748b;}
.header{border-bottom:2px solid #0284c7;padding-bottom:10px;margin-bottom:16px;}
.header-right{float:right;text-align:right;font-size:10pt;font-weight:bold;}
.bill-no{font-family:monospace;color:#0284c7;font-size:12pt;}
.box{background:#f8fafc;border:1px solid #e2e8f0;padding:10px 14px;margin-bottom:12px;}
.half{display:inline-block;width:48%;vertical-align:top;}
.half-right{display:inline-block;width:48%;vertical-align:top;padding-left:10px;border-left:1px solid #e2e8f0;}
table{width:100%;border-collapse:collapse;margin-bottom:12px;}
th{background:#f1f5f9;color:#64748b;font-size:8.5pt;text-transform:uppercase;padding:6px 8px;border:1px solid #e2e8f0;text-align:left;}
td{padding:7px 8px;border:1px solid #e2e8f0;font-size:10pt;}
.tr{text-align:right;}
.totalrow{background:#e0f2fe;font-weight:bold;font-size:12pt;}
.badge{display:inline-block;padding:2px 12px;border-radius:20px;font-size:10pt;font-weight:bold;border:1.5px solid '.$statusColor.';color:'.$statusColor.';}
.lbl{color:#64748b;font-size:8.5pt;text-transform:uppercase;}
.val{font-weight:bold;font-size:10.5pt;}
.note{margin-top:18px;font-size:8.5pt;color:#64748b;border-top:1px solid #e2e8f0;padding-top:8px;}
.red{color:#b91c1c;}
';

        $logoPath = __DIR__ . '/../../public/images/logo.jpg';
        $logoTag  = file_exists($logoPath)
            ? '<img src="data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) . '" style="width:38px;height:38px;border-radius:50%;vertical-align:middle;margin-right:8px;">'
            : '&#x1F4A7; ';

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>';
        $html .= '<div class="header">';
        $html .= '<div class="header-right">STATEMENT OF WATER ACCOUNT<br><span class="bill-no">' . $billNo . '</span><br><span class="sub">Period: ' . $period . '</span></div>';
        $html .= '<h2>' . $logoTag . 'La Mesa Water District</h2><div class="sub">' . $addr . $phoneStr . '</div>';
        $html .= '</div>';

        // Customer + Meter info
        $html .= '<div class="box">';
        $html .= '<div class="half">';
        $html .= '<div class="lbl">Customer Name</div><div class="val">' . $name . '</div>';
        $html .= '<div style="font-size:9.5pt;color:#475569;margin-top:3px;">' . $addr2 . ', ' . $bgy . '</div>';
        $html .= '<div style="margin-top:6px;font-size:10pt;"><span class="lbl">Account #:</span> <strong style="font-family:monospace;color:#0284c7;">' . $acct . '</strong> &nbsp; <span class="lbl">Type:</span> <strong>' . $type . '</strong></div>';
        $html .= '</div>';
        $html .= '<div class="half-right">';
        $html .= '<div class="lbl">Meter Serial #</div><div class="val" style="font-family:monospace;">' . $meter . '</div>';
        $html .= '<div style="margin-top:6px;font-size:10pt;">';
        $html .= '<span class="lbl">Prev Reading:</span> <strong>' . $prev . '</strong><br>';
        $html .= '<span class="lbl">Curr Reading:</span> <strong>' . $curr . '</strong><br>';
        $html .= '<span class="lbl">Consumption:</span> <strong style="color:#0284c7;">' . $consF . ' m&sup3;</strong>';
        $html .= '</div></div></div>';

        // Charges table
        $html .= '<table><thead><tr><th>Description</th><th>Rate Applied</th><th class="tr">Amount (&#8369;)</th></tr></thead><tbody>';
        $html .= '<tr><td>Base Minimum Charge (First 10 m&sup3;) &mdash; ' . $type . '</td><td>&#8369;' . $minCh . '</td><td class="tr"><strong>&#8369;' . $minCh . '</strong></td></tr>';

        if ($cons > 10) {
            $excess = number_format(($cons - 10) * (float)($bill['applied_rate_per_m3'] ?? 0), 2);
            $html .= '<tr><td>Excess Consumption &mdash; ' . number_format($cons - 10) . ' m&sup3; &times; &#8369;' . $rateM3 . '/m&sup3;</td><td>&#8369;' . $rateM3 . '/m&sup3;</td><td class="tr"><strong>&#8369;' . $excess . '</strong></td></tr>';
        }
        if ($prevUnpaid > 0) {
            $html .= '<tr><td class="red">Previous Unpaid / Arrears</td><td>&mdash;</td><td class="tr red"><strong>&#8369;' . number_format($prevUnpaid, 2) . '</strong></td></tr>';
        }
        if ($penalty > 0) {
            $html .= '<tr><td class="red">Overdue Penalty Charge</td><td>&mdash;</td><td class="tr red"><strong>&#8369;' . number_format($penalty, 2) . '</strong></td></tr>';
        }

        $html .= '</tbody><tfoot><tr class="totalrow"><td colspan="2" style="text-align:right;">TOTAL AMOUNT DUE:</td><td class="tr">&#8369;' . $total . '</td></tr></tfoot></table>';

        // Footer
        $html .= '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">';
        $html .= '<div><span style="font-size:10pt;">Please pay on or before: <strong class="red">' . $due . '</strong></span><br><span class="sub">Overdue accounts are subject to penalty charges after due date.</span></div>';
        $html .= '<div><span class="badge">' . $status . '</span></div>';
        $html .= '</div>';
        $html .= '<div class="note">This is a computer-generated document. No signature required. For inquiries, contact the La Mesa Water District office.</div>';
        $html .= '</body></html>';

        return $html;
    }
}
