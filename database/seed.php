<?php
// database/seed.php

require_once __DIR__ . '/../config/database.php';

echo "Starting Database Seeder...\n";

try {
    $pdo = Database::getConnection();

    // 1. Clear existing data safely
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE payment_details;");
    $pdo->exec("TRUNCATE TABLE payments;");
    $pdo->exec("TRUNCATE TABLE billing;");
    $pdo->exec("TRUNCATE TABLE meter_readings;");
    $pdo->exec("TRUNCATE TABLE meters;");
    $pdo->exec("TRUNCATE TABLE customer_accounts;");
    $pdo->exec("TRUNCATE TABLE customers;");
    $pdo->exec("TRUNCATE TABLE water_rates;");
    $pdo->exec("TRUNCATE TABLE barangays;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $pdo->beginTransaction();

    // 2. Seed Barangays
    $barangays = [
        'Greater Lagro', 'Fairview', 'Pasong Putik', 'Kaligayahan', 
        'San Jose', 'Sacred Heart', 'Nagkaisang Nayon', 'Gulod'
    ];
    $bStmt = $pdo->prepare("INSERT INTO barangays (name) VALUES (?)");
    foreach ($barangays as $b) {
        $bStmt->execute([$b]);
    }
    
    // Fetch barangay IDs
    $bList = $pdo->query("SELECT id FROM barangays")->fetchAll(PDO::FETCH_COLUMN);

    // 3. Seed Water Rates
    $rStmt = $pdo->prepare("INSERT INTO water_rates (account_type, min_consumption, min_rate, rate_per_m3, penalty_rate, effective_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $rStmt->execute(['Residential', 10, 180.00, 22.50, 10.00, '2026-01-01', 'Active']);
    $rStmt->execute(['Commercial', 10, 450.00, 48.00, 10.00, '2026-01-01', 'Active']);
    $rStmt->execute(['Government', 10, 300.00, 32.00, 10.00, '2026-01-01', 'Active']);

    // 4. Seed Default Employees / Users
    $eStmt = $pdo->prepare("INSERT IGNORE INTO employees (employee_number, full_name, username, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $passHash = password_hash('password123', PASSWORD_DEFAULT);
    
    $eStmt->execute(['EMP-0001', 'Admin System', 'admin', $passHash, 'Administrator', 'Active']);
    $eStmt->execute(['EMP-0002', 'Maria Billing', 'billing', $passHash, 'Billing Staff', 'Active']);
    $eStmt->execute(['EMP-0003', 'Juan Cashier', 'cashier', $passHash, 'Cashier', 'Active']);
    $eStmt->execute(['EMP-0004', 'Elena Manager', 'manager', $passHash, 'Manager', 'Active']);

    // 5. Seed 500+ Customers, Accounts, Meters, Readings & Billing History
    $firstNames = ['Juan', 'Maria', 'Pedro', 'Ana', 'Jose', 'Carla', 'Mark', 'Grace', 'Antonio', 'Liza', 'Ramon', 'Teresa', 'Eduardo', 'Sarah', 'Gabriel', 'Rosa', 'David', 'Elena', 'Fernando', 'Patricia'];
    $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Gonzales', 'Bautista', 'Villanueva', 'Ramos', 'Castillo', 'Mendoza', 'Aquino', 'Garcia', 'Torres', 'Navarro', 'Mercado', 'Flores', 'Salazar', 'Cruz', 'Ocampo', 'Santiago', 'Del Rosario'];
    $types = ['Residential', 'Residential', 'Residential', 'Residential', 'Commercial', 'Government'];

    $cStmt = $pdo->prepare("INSERT INTO customers (customer_code, first_name, middle_name, last_name, contact_number, email, address, barangay_id, date_connected, account_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $aStmt = $pdo->prepare("INSERT INTO customer_accounts (account_number, customer_id, current_balance, previous_balance, total_amount_due, status) VALUES (?, ?, ?, ?, ?, ?)");
    $mStmt = $pdo->prepare("INSERT INTO meters (meter_number, brand_model, installation_date, account_id, status) VALUES (?, ?, ?, ?, ?)");
    $rStmt = $pdo->prepare("INSERT INTO meter_readings (account_id, meter_id, reading_date, previous_reading, current_reading, reader_name, is_anomaly, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $billStmt = $pdo->prepare("INSERT INTO billing (bill_number, account_id, meter_reading_id, billing_period, prev_reading_val, curr_reading_val, consumption_val, applied_min_rate, applied_rate_per_m3, subtotal, tax, penalty_amount, previous_unpaid, total_amount, amount_paid, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $payStmt = $pdo->prepare("INSERT INTO payments (or_number, account_id, cashier_id, payment_date, amount_paid, payment_method, reference_number, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $pdStmt = $pdo->prepare("INSERT INTO payment_details (payment_id, billing_id, amount_applied) VALUES (?, ?, ?)");

    $readerNames = ['Benito Reader', 'Carlos Inspector', 'Danilo Fieldman'];

    // Cashier ID for payments
    $cashierId = $pdo->query("SELECT id FROM employees WHERE role='Cashier' LIMIT 1")->fetchColumn();

    echo "Seeding 500 Customer Accounts...\n";

    for ($i = 1; $i <= 500; $i++) {
        $fn = $firstNames[array_rand($firstNames)];
        $ln = $lastNames[array_rand($lastNames)];
        $mn = $firstNames[array_rand($firstNames)];
        $code = 'CUST-' . str_pad($i, 5, '0', STR_PAD_LEFT);
        $acctNo = 'ACCT-' . str_pad($i, 5, '0', STR_PAD_LEFT);
        $phone = '09' . rand(100000000, 999999999);
        $email = strtolower($fn . '.' . $ln . $i . '@example.com');
        $bId = $bList[array_rand($bList)];
        $type = $types[array_rand($types)];
        $connDate = date('Y-m-d', strtotime('-' . rand(10, 60) . ' months'));

        $cStmt->execute([$code, $fn, $mn, $ln, $phone, $email, "Block " . rand(1, 20) . " Lot " . rand(1, 30) . " Waterworks St.", $bId, $connDate, $type, 'Active']);
        $customerId = $pdo->lastInsertId();

        $aStmt->execute([$acctNo, $customerId, 0.00, 0.00, 0.00, 'Active']);
        $accountId = $pdo->lastInsertId();

        $meterNo = 'MTR-' . str_pad($i, 5, '0', STR_PAD_LEFT);
        $mStmt->execute([$meterNo, 'Itron 20mm', $connDate, $accountId, 'Active']);
        $meterId = $pdo->lastInsertId();

        // Historical readings and bills for past 3 months
        $currentVal = rand(50, 200);
        $runningBalance = 0.00;

        for ($m = 3; $m >= 1; $m--) {
            $period = date('Y-m-01', strtotime("-$m month"));
            $readDate = date('Y-m-25', strtotime("-$m month"));
            $dueDate = date('Y-m-10', strtotime("-" . ($m - 1) . " month"));

            $prevVal = $currentVal;
            $consumption = rand(8, 35);
            $currentVal = $prevVal + $consumption;

            $reader = $readerNames[array_rand($readerNames)];
            $isAnomaly = ($consumption > 30) ? 1 : 0;

            $rStmt->execute([$accountId, $meterId, $readDate, $prevVal, $currentVal, $reader, $isAnomaly, $isAnomaly ? 'High usage warning' : null]);
            $readingId = $pdo->lastInsertId();

            // Calculate Bill
            $minCons = 10;
            $minRate = ($type === 'Residential') ? 180.00 : (($type === 'Commercial') ? 450.00 : 300.00);
            $ratePerM3 = ($type === 'Residential') ? 22.50 : (($type === 'Commercial') ? 48.00 : 32.00);

            if ($consumption <= $minCons) {
                $subtotal = $minRate;
            } else {
                $subtotal = $minRate + (($consumption - $minCons) * $ratePerM3);
            }
            $tax = 0.00;
            $penalty = 0.00;
            $billTotal = $subtotal + $tax + $runningBalance;

            $billNo = 'BILL-' . date('Ym', strtotime($period)) . '-' . str_pad($accountId, 5, '0', STR_PAD_LEFT);

            // Randomize payment status for historical bills
            $status = 'Paid';
            $paidAmt = $billTotal;

            if ($m === 1 && rand(1, 10) > 7) { // 30% unpaid for recent month
                $status = 'Unpaid';
                $paidAmt = 0.00;
                $runningBalance += $billTotal;
            }

            $billStmt->execute([
                $billNo, $accountId, $readingId, $period, $prevVal, $currentVal, $consumption,
                $minRate, $ratePerM3, $subtotal, $tax, $penalty, 0.00, $billTotal, $paidAmt, $dueDate, $status
            ]);
            $billId = $pdo->lastInsertId();

            if ($status === 'Paid') {
                $orNo = 'OR-' . date('Ym', strtotime($readDate)) . '-' . str_pad($billId, 5, '0', STR_PAD_LEFT);
                $payStmt->execute([$orNo, $accountId, $cashierId, date('Y-m-d H:i:s', strtotime($readDate . ' +3 days')), $billTotal, 'Cash', null, 'Full Settlement']);
                $payId = $pdo->lastInsertId();
                $pdStmt->execute([$payId, $billId, $billTotal]);
            }
        }

        // Update Account total balance
        $pdo->prepare("UPDATE customer_accounts SET current_balance = ?, total_amount_due = ? WHERE id = ?")
            ->execute([$runningBalance, $runningBalance, $accountId]);
    }

    // Seed System Settings
    $settings = [
        ['company_name', 'La Mesa Water District', 'Official District Name'],
        ['company_address', 'Quirino Highway, Novaliches, Quezon City', 'District Address'],
        ['contact_number', '(02) 8923-4567', 'Hotline Number'],
        ['billing_due_days', '15', 'Days until bill due date'],
        ['penalty_percentage', '10', 'Overdue penalty rate (%)']
    ];
    $sStmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach ($settings as $s) {
        $sStmt->execute($s);
    }

    $pdo->commit();
    echo "SUCCESS: Database seeded with 500 customers, meters, readings, bills, payments, and default staff users.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Seeding error: " . $e->getMessage() . "\n";
    exit(1);
}
