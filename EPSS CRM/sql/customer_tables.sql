-- Create customer_documents table
CREATE TABLE IF NOT EXISTS customer_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by VARCHAR(50) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Create customer_history table
CREATE TABLE IF NOT EXISTS customer_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    changes TEXT NOT NULL,
    changed_by VARCHAR(50) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Add new columns to customers table
ALTER TABLE customers
ADD COLUMN IF NOT EXISTS contact_person VARCHAR(100) AFTER address,
ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(20) AFTER contact_person,
ADD COLUMN IF NOT EXISTS operating_hours VARCHAR(100) AFTER contact_phone,
ADD COLUMN IF NOT EXISTS facility_type VARCHAR(50) AFTER operating_hours,
ADD COLUMN IF NOT EXISTS capacity INT AFTER facility_type,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active' AFTER capacity;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_customer_documents_customer_id ON customer_documents(customer_id);
CREATE INDEX IF NOT EXISTS idx_customer_history_customer_id ON customer_history(customer_id);
CREATE INDEX IF NOT EXISTS idx_customer_history_changed_at ON customer_history(changed_at);
CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(status);
CREATE INDEX IF NOT EXISTS idx_customers_region ON customers(region);
CREATE INDEX IF NOT EXISTS idx_customers_zone ON customers(zone);
CREATE INDEX IF NOT EXISTS idx_customers_level ON customers(level); 