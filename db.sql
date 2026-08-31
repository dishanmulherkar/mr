-- purchase entry table create 

CREATE TABLE purchase_entry (
    purchase_id INT AUTO_INCREMENT PRIMARY KEY,

    purchase_no VARCHAR(30) NOT NULL UNIQUE,

    supplier_id INT NOT NULL,

    invoice_no VARCHAR(100) NOT NULL,

    invoice_date DATE NOT NULL,

    purchase_date DATE NOT NULL,

    total_qty DECIMAL(12,2) NOT NULL DEFAULT 0,

    sub_total DECIMAL(12,2) NOT NULL DEFAULT 0,

    discount DECIMAL(12,2) NOT NULL DEFAULT 0,

    gst_amount DECIMAL(12,2) NOT NULL DEFAULT 0,

    other_charges DECIMAL(12,2) NOT NULL DEFAULT 0,

    grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,

    remarks TEXT NULL,

    status ENUM('Draft','Completed','Cancelled')
        DEFAULT 'Completed',

    created_by INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- purchase table details 


CREATE TABLE purchase_entry_details (

    detail_id INT AUTO_INCREMENT PRIMARY KEY,

    purchase_id INT NOT NULL,

    product_id INT NOT NULL,

    batch_id INT DEFAULT NULL,

    batch_no VARCHAR(50) NOT NULL,

    expiry_date DATE NULL,

    purchase_rate DECIMAL(12,2) NOT NULL,

    mrp DECIMAL(12,2) DEFAULT 0,

    qty DECIMAL(12,2) NOT NULL,

    free_qty DECIMAL(12,2) DEFAULT 0,

    amount DECIMAL(12,2) NOT NULL,

    available_qty DECIMAL(12,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_purchase
        FOREIGN KEY (purchase_id)
        REFERENCES purchase_entry(purchase_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_purchase_product
        FOREIGN KEY (product_id)
        REFERENCES products(p_id)
);

-- add  field in product batches table for purchase rate and amt 

ALTER TABLE product_batches
ADD COLUMN purchase_rate DECIMAL(10, 2) DEFAULT 0.00 AFTER updated_at,
ADD COLUMN purchase_tax DECIMAL(10, 2) DEFAULT 0.00 AFTER purchase_rate,
ADD COLUMN sale_rate DECIMAL(10, 2) DEFAULT 0.00 AFTER purchase_tax,
ADD COLUMN sale_tax DECIMAL(10, 2) DEFAULT 0.00 AFTER sale_rate,
ADD COLUMN expiry_date DATE NULL AFTER sale_tax,
ADD COLUMN mrp DECIMAL(10, 2) DEFAULT 0.00 AFTER expiry_date,
ADD COLUMN disc DECIMAL(5,2) NOT NULL DEFAULT '0.00';


-- update stock ledger table 
ALTER TABLE stock_ledger
MODIFY qty DECIMAL(10,3) NOT NULL DEFAULT 0.000,
ADD qty_in DECIMAL(10,3) NOT NULL DEFAULT 0.000 AFTER trans_type,
ADD qty_out DECIMAL(10,3) NOT NULL DEFAULT 0.000 AFTER qty_in,
ADD approved_by INT NULL AFTER admin_id,
ADD reference_table VARCHAR(50) NULL AFTER amount,
ADD rate DECIMAL(10,3) NOT NULL DEFAULT 0.000 AFTER `qty`,
ADD `stockist_type` VARCHAR(50) NULL AFTER `trans_datetime`,
MODIFY trans_type ENUM(
'OPENING',
'PURCHASE',
'INWARD',
'SALE',
'PURCHASE_RETURN',
'SALES_RETURN',
'ADJUSTMENT'
);


-- alter table of mr_users


--  drag and drop headquarter 


-- similer with order table and order item table  
CREATE TABLE `orders` (
  `order_id` INT AUTO_INCREMENT PRIMARY KEY,
  `stockist_id` INT NOT NULL,
  `mr_id` INT NOT NULL,
  `total_amt` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Processed') DEFAULT 'Pending',
  `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`stockist_id`),
  INDEX (`mr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `order_details` (
  `detail_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL COMMENT 'Quantity originally ordered by MR',
  `approved_qty` INT DEFAULT NULL COMMENT 'Quantity approved (can be changed later)',
  `rate` DECIMAL(10, 2) NOT NULL COMMENT 'PTS rate',
  `discount` DECIMAL(5, 2) NOT NULL DEFAULT 16.66 COMMENT 'Discount percentage',
  `gst` DECIMAL(5, 2) NOT NULL DEFAULT 0.00 COMMENT 'GST percentage',
  `amt` DECIMAL(12, 2) NOT NULL COMMENT 'Taxable amount (Qty x Net Rate)',
  `net_total` DECIMAL(12, 2) NOT NULL COMMENT 'Final amount including GST',
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  INDEX (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `order_details` ADD COLUMN `batch_id` INT NOT NULL AFTER `product_id`;

-- stock inward alter 

ALTER TABLE stock_inward   /done
    ADD COLUMN mr_id INT NOT NULL DEFAULT 0 AFTER stockist_id,
    ADD COLUMN order_id INT DEFAULT NULL AFTER mr_id,
    ADD COLUMN inward_no VARCHAR(30) NULL UNIQUE AFTER inward_id,
    ADD COLUMN total_qty DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER inward_date,
    ADD COLUMN sub_total DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_qty,
    ADD COLUMN discount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER sub_total,
    ADD COLUMN gst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER discount,
    ADD COLUMN other_charges DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER gst_amount,
    ADD COLUMN grand_total DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER other_charges,
        ADD COLUMN cgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER grand_total,
    ADD COLUMN sgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER cgst_amount,
    ADD COLUMN igst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER sgst_amount,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN stockist_name VARCHAR(255) NULL,
    ADD COLUMN gst_no VARCHAR(50) NULL,
      ADD COLUMN super_stockist_id INT NOT NULL DEFAULT 0 ;


    ALTER TABLE `stock_inward_details` 
  ADD COLUMN `amt` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  ADD COLUMN `net_total` DECIMAL(12,2) NOT NULL DEFAULT '0.00';

  ALTER TABLE stockists
ADD COLUMN gst_no VARCHAR(20) NULL AFTER address,
ADD COLUMN gst_type ENUM('CGST_SGST','IGST','VAT') NOT NULL DEFAULT 'CGST_SGST' AFTER gst_no,
ADD COLUMN dispatch_to VARCHAR(255) NULL AFTER gst_type,
ADD COLUMN transport VARCHAR(100) NULL AFTER dispatch_to;

ALTER TABLE stockists
ADD COLUMN pan_no VARCHAR(10) NULL AFTER `gst_type`,
ADD COLUMN dl_no VARCHAR(50) NULL AFTER pan_no;

ALTER TABLE mr_users
ADD COLUMN credit_limit DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER hq_id,
 ADD hq_id INT NOT NULL AFTER m_id ;


  ALTER TABLE super_stockist
  ADD COLUMN gst_no VARCHAR(20) NULL AFTER 	pincode,
  ADD COLUMN address TEXT NULL AFTER gst_no;

ALTER TABLE products
ADD COLUMN hsn_code VARCHAR(20) NULL AFTER product_name;

ALTER TABLE `stock_inward_details`
ADD COLUMN `mrp` DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `batch_id`,
ADD COLUMN `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT '0.00' AFTER `rate`,
ADD COLUMN `gst_percent` DECIMAL(5,2) NOT NULL DEFAULT '0.00' AFTER `amt`,
ADD COLUMN `gst_amount` DECIMAL(12,2) NOT NULL DEFAULT '0.00' AFTER `gst_percent`;


CREATE TABLE payment_details (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    stockist_id BIGINT NOT NULL,
    amount_paid DECIMAL(15, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL, -- e.g., 'GPay', 'Bank Transfer', 'Cash'
    bank_details TEXT NULL,              -- Transaction IDs, UTR, or Account info
    screenshot_path VARCHAR(255) NULL,   -- Path to the uploaded GPay/Bank screenshot
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by BIGINT NULL,             -- Stores the ID of the sub-admin who approved/rejected
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for faster lookups
    INDEX idx_stockist (stockist_id),
    INDEX idx_status (approval_status)
);

CREATE TABLE payment_ledgers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    stockist_id BIGINT NOT NULL,
    transaction_type ENUM('bill_added', 'payment_made') NOT NULL,
    reference_id BIGINT NOT NULL, -- Ties back to stock_inwards.id OR payment_details.id
    amount DECIMAL(15, 2) NOT NULL,
    balance_action ENUM('increase_debt', 'decrease_debt') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for faster lookups
    INDEX idx_ledger_stockist (stockist_id),
    INDEX idx_reference (transaction_type, reference_id)
);




ALTER TABLE `stock_inward` 
ADD COLUMN `cd_percent` DECIMAL(5,2) NOT NULL DEFAULT '0.00',
ADD COLUMN `cd_amt` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ;



  

  ALTER TABLE stock_inward
ADD lr_no VARCHAR(100) NULL AFTER 	order_id,
ADD eway_bill_no VARCHAR(100) NULL AFTER lr_no,
ADD vehicle_no VARCHAR(50) NULL AFTER eway_bill_no,
ADD transport_name VARCHAR(255) NULL AFTER vehicle_no,
ADD credit_days INT DEFAULT 0 AFTER transport_name;

-- 12-8-26
ALTER TABLE super_stockist 
ADD term_and_condition TEXT NULL;

-- Modify your existing column to allow the new mobile roles
ALTER TABLE admins 
MODIFY COLUMN role ENUM('Super Admin', 'Admin', 'ASM', 'Dispatch') NOT NULL;
ALTER TABLE `admins` 
  ADD COLUMN stockist_id BIGINT NOT NULL ;

-- date 13-8-26
ALTER TABLE headquarter ADD asm_id INT NULL;

-- 14-8-26

ALTER TABLE sales_entries 
ADD total_pts_amt DECIMAL(15,2) NOT NULL DEFAULT '0.00' AFTER c_id;
ALTER TABLE sales_details 
ADD pts_rate DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER rate,
ADD pts_amt DECIMAL(12,2) NOT NULL DEFAULT '0.00' AFTER pts_rate;

-- Add PTS rate and line amount to inward details
ALTER TABLE stock_inward_details 
ADD pts_rate DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER mrp,
ADD pts_amt DECIMAL(12,2) NOT NULL DEFAULT '0.00' AFTER pts_rate;

-- Add Total PTS amount to the main inward header
ALTER TABLE stock_inward 
ADD total_pts_amt DECIMAL(15,2) NOT NULL DEFAULT '0.00' AFTER grand_total;

-- pending
ALTER TABLE stock_ledger 
ADD pts_rate DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER amt,
ADD pts_amt DECIMAL(12,2) NOT NULL DEFAULT '0.00' AFTER pts_rate;

-- 17-8-26
ALTER TABLE `sales_entries` 
ADD COLUMN `mrp_total` DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `total_amt`;

-- 18-8-26
ALTER TABLE `stock_inward` 
  ADD COLUMN drc BIGINT NOT NULL ,
  ADD COLUMN mrc BIGINT NOT NULL ,
  ADD COLUMN paid_amt DECIMAL(10,2) NOT NULL DEFAULT 0.00,
ADD COLUMN pay_status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid';

CREATE TABLE payment_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Changed to BIGINT to match payment_ledgers
    ledger_id BIGINT NOT NULL, 
    
    inward_id INT NOT NULL, 
    amount_allocated DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ledger_id) REFERENCES payment_ledgers(id) ON DELETE CASCADE,
    FOREIGN KEY (inward_id) REFERENCES stock_inward(inward_id) ON DELETE CASCADE
);

ALTER TABLE mr_users 
ADD COLUMN commission_rate DECIMAL(5,2) NOT NULL DEFAULT 7.00;

-- 1. Table to store the main payout record
CREATE TABLE commission_payouts (
    payout_id INT AUTO_INCREMENT PRIMARY KEY,
    hq_id INT NOT NULL,
    total_payout DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table to store the multiple dynamic adjustments
CREATE TABLE commission_adjustments (
    adjustment_id INT AUTO_INCREMENT PRIMARY KEY,
    payout_id INT NOT NULL,
    description VARCHAR(255),
    adj_type ENUM('+', '-') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (payout_id) REFERENCES commission_payouts(payout_id) ON DELETE CASCADE
);

ALTER TABLE stock_inward ADD COLUMN commission_payout_id INT NULL;

ALTER TABLE commission_payouts 
ADD COLUMN status ENUM('Pending', 'Paid') DEFAULT 'Pending' AFTER total_payout;

ALTER TABLE commission_payouts 
ADD COLUMN commission_type ENUM('MR', 'DRC') DEFAULT 'MR' AFTER hq_id;
-----------------------
-- 21/8/26  -----------
-----------------------
ALTER TABLE `payment_ledgers`
  ADD COLUMN `ledger_type` ENUM('debt', 'mrc_wallet', 'drc_wallet') NOT NULL DEFAULT 'debt' AFTER `stockist_id`,
  ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `balance_action`,
  ADD KEY `idx_ledger_type` (`ledger_type`),
  MODIFY COLUMN `transaction_type` ENUM(
      'bill_added', 'payment_made', 'mrc_settlement', 'drc_settlement', 
      'commission_earned', 'settled_to_bill', 'paid_to_bank'
  ) NOT NULL;

  -- Step 2A: Expand the ENUM to temporarily accept old and new values
ALTER TABLE `payment_ledgers`
  MODIFY COLUMN `balance_action` ENUM('increase_debt', 'decrease_debt', 'increase', 'decrease') NOT NULL;

-- Step 2B: Convert the old data to the new terminology
UPDATE `payment_ledgers` SET `balance_action` = 'increase' WHERE `balance_action` = 'increase_debt';
UPDATE `payment_ledgers` SET `balance_action` = 'decrease' WHERE `balance_action` = 'decrease_debt';

-- Step 2C: Lock the ENUM to strictly use the new values moving forward
ALTER TABLE `payment_ledgers`
  MODIFY COLUMN `balance_action` ENUM('increase', 'decrease') NOT NULL;

  ALTER TABLE `payment_details`
ADD COLUMN `commission_type` ENUM('none', 'mrc', 'drc') DEFAULT 'none' AFTER `payment_method`;

-----------------------
-- 22/8/26  -----------
-----------------------
ALTER TABLE payment_details MODIFY COLUMN approval_status ENUM('pending', 'approved', 'rejected', 'reversed') DEFAULT 'pending';

-- 25/8/26 -----
----------------
CREATE TABLE super_stockist_cd_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    super_stockist_id INT NOT NULL UNIQUE,
    cd_4_percent_days INT NOT NULL DEFAULT 10,
    cd_2_percent_days INT NOT NULL DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE `stock_inward` CHANGE `drc` `drc` BIGINT NULL DEFAULT '0';


----31-08-26 
-- Add round_off to the Orders table
ALTER TABLE `orders` 
ADD `round_off` DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `total_amt`;


ALTER TABLE `stock_inward` 
ADD `round_off` DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `other_charges`;


---- 31-08-26

CREATE TABLE banks (
    bank_id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some default Indian banks to get you started
INSERT INTO banks (bank_name) VALUES 
('State Bank of India (SBI)'), ('HDFC Bank'), ('ICICI Bank'), 
('Axis Bank'), ('Kotak Mahindra Bank'), ('Bank of Baroda'), 
('Punjab National Bank (PNB)'), ('Canara Bank'), ('Union Bank of India'), ('IDFC First Bank');


CREATE TABLE super_stockist_banks (
    super_stockist_id INT NOT NULL,
    bank_id INT NOT NULL,
    
    -- Composite primary key prevents assigning the exact same bank twice to the same person
    PRIMARY KEY (super_stockist_id, bank_id)
);


ALTER TABLE payment_details 
ADD bank_id INT NULL COMMENT 'Links to banks master table' AFTER payment_method;