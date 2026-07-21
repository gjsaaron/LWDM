-- database/migrations/001_initial_schema.sql

CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS water_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type ENUM('Residential', 'Commercial', 'Government') NOT NULL,
    min_consumption INT NOT NULL DEFAULT 10,
    min_rate DECIMAL(10,2) NOT NULL,
    rate_per_m3 DECIMAL(10,2) NOT NULL,
    penalty_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00, -- 10% penalty
    effective_date DATE NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(30) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT NOT NULL,
    barangay_id INT NOT NULL,
    date_connected DATE NOT NULL,
    account_type ENUM('Residential', 'Commercial', 'Government') NOT NULL DEFAULT 'Residential',
    status ENUM('Active', 'Disconnected', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id)
);

CREATE TABLE IF NOT EXISTS customer_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    current_balance DECIMAL(10,2) DEFAULT 0.00,
    previous_balance DECIMAL(10,2) DEFAULT 0.00,
    total_amount_due DECIMAL(10,2) DEFAULT 0.00,
    last_payment_date DATE NULL,
    status ENUM('Active', 'Inactive', 'Disconnected') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS meters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_number VARCHAR(50) UNIQUE NOT NULL,
    brand_model VARCHAR(100),
    installation_date DATE NOT NULL,
    account_id INT NOT NULL,
    status ENUM('Active', 'Replaced', 'Faulty') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES customer_accounts(id)
);

CREATE TABLE IF NOT EXISTS meter_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    meter_id INT NOT NULL,
    reading_date DATE NOT NULL,
    previous_reading INT NOT NULL,
    current_reading INT NOT NULL,
    consumption INT AS (current_reading - previous_reading) STORED,
    reader_name VARCHAR(100) NOT NULL,
    is_anomaly TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES customer_accounts(id),
    FOREIGN KEY (meter_id) REFERENCES meters(id)
);

CREATE TABLE IF NOT EXISTS billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(30) UNIQUE NOT NULL,
    account_id INT NOT NULL,
    meter_reading_id INT NOT NULL,
    billing_period DATE NOT NULL,
    prev_reading_val INT NOT NULL,
    curr_reading_val INT NOT NULL,
    consumption_val INT NOT NULL,
    applied_min_rate DECIMAL(10,2) NOT NULL,
    applied_rate_per_m3 DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0.00,
    penalty_amount DECIMAL(10,2) DEFAULT 0.00,
    previous_unpaid DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    due_date DATE NOT NULL,
    status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Overdue') DEFAULT 'Unpaid',
    is_adjusted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES customer_accounts(id),
    FOREIGN KEY (meter_reading_id) REFERENCES meter_readings(id)
);

CREATE TABLE IF NOT EXISTS billing_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billing_id INT NOT NULL,
    type ENUM('Credit', 'Debit') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT NOT NULL,
    adjusted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (billing_id) REFERENCES billing(id)
);

CREATE TABLE IF NOT EXISTS billing_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billing_period DATE NOT NULL,
    total_accounts INT DEFAULT 0,
    processed_accounts INT DEFAULT 0,
    failed_accounts INT DEFAULT 0,
    status ENUM('Pending', 'In Progress', 'Completed', 'Failed') DEFAULT 'Pending',
    error_log JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    or_number VARCHAR(30) UNIQUE NOT NULL,
    account_id INT NOT NULL,
    cashier_id INT NOT NULL,
    payment_date DATETIME NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Check', 'Online Bank', 'GCash/PayMaya') DEFAULT 'Cash',
    reference_number VARCHAR(100),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (account_id) REFERENCES customer_accounts(id)
);

CREATE TABLE IF NOT EXISTS payment_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    billing_id INT NOT NULL,
    amount_applied DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (billing_id) REFERENCES billing(id)
);

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(30) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Administrator', 'Billing Staff', 'Cashier', 'Manager') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NULL,
    employee_name VARCHAR(100),
    action VARCHAR(100) NOT NULL,
    affected_record VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    document_type ENUM('Valid ID', 'Proof of Address', 'Application Form', 'Other') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(150) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    description VARCHAR(255)
);
