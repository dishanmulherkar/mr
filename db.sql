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
DROP COLUMN pts,
ADD purchase_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
ADD available_qty INT NOT NULL DEFAULT 0,
ADD expiry_date DATE NULL;


-- add table for state wise price of product 

CREATE TABLE product_state_price
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    state_id INT NOT NULL,
    pts DECIMAL(10,2) NOT NULL,
    mrp DECIMAL(10,2) DEFAULT 0,
    effective_date DATE NOT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active'
);

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
 ALTER TABLE mr_users
 ADD hq_id INT NOT NULL AFTER m_id ;

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