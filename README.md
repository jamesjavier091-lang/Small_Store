# Small Store POS

## Project Description
The Small Store POS is a simple Point of Sale and Inventory Management System designed for a small store.
The system helps the store manage products, categories, users, inventory, sales transactions, receipts, and sales reports.

## Technologies Used
- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap 5
- Bootstrap Icons
- XAMPP
- SQLyog

## Main Features
### Login and User Management
- User login
- User registration
- Admin and Cashier roles
- Add users
- Edit users
- Delete users
- Password hashing
### Category Management
- Add category
- Edit category
- Delete category
- View categories
### Product Management
- Add products
- Edit products
- Delete products
- View products
- Manage product prices
- Manage product stock
- Low-stock monitoring
### Cashier / POS
- Search products
- Add products to cart
- Update cart quantity
- Remove products from cart
- Check available stock
- Calculate total
- Enter cash payment
- Calculate change
- Complete sales transaction
- Automatically update product stock
### Receipt
- Display receipt number
- Display date and cashier
- Display purchased products
- Display quantity and price
- Display subtotal
- Display total
- Display cash payment
- Display change
- Print receipt
### Sales History
- View previous sales
- Search by receipt number
- Search by date
- View receipt
- Display total sales
### Reports
- Monthly sales
- Daily sales
- Best-selling products
- Sales summary
## Database
Database name:
`sari_sari_pos`
Main tables:
- `users`
- `categories`
- `products`
- `cart`
- `sales`
- `sale_items`
## Requirements
Before running the system, install:
- XAMPP
- MySQL
- Web browser
- SQLyog or phpMyAdmin

## Installation
### 1. Install XAMPP
Install XAMPP on your computer.

### 2. Start XAMPP
Open XAMPP Control Panel and start:
- Apache
- MySQL

### 3. Copy the Project
Copy the project folder into: `C:\xampp\htdocs\`
Example: `C:\xampp\htdocs\small_store_pos`

### 4. Create the Database
Open SQLyog or phpMyAdmin.
Create a database named: `sari_sari_pos`

### 5. Import the Database
Import the file: `sari_sari_pos.sql`
This file contains the database structure and data needed by the system.

### 6. Check Database Connection
The system uses the following database connection:
- Host: `localhost`
- Username: `root`
- Password: empty
- Database: `sari_sari_pos`

### 7. Run the System
Open a web browser and go to: `http://localhost/small_store_pos/`

## User Roles
### Admin
The Admin can access:
- Dashboard
- Categories
- Products
- Users
- Cashier
- Sales
- Reports

### Cashier
The Cashier can access:
- Dashboard
- Cashier
- Sales
- Reports

## Security Features
The system includes:
- Password hashing
- Prepared statements
- Input validation
- Stock validation
- Role-based access
- Protection against negative stock
- Protection against SQL injection

## Project Structure
small_store_pos/
│
├── cashier.php
├── categories.php
├── config.php
├── dashboard.php
├── login.php
├── logout.php
├── products.php
├── receipt.php
├── register.php
├── reports.php
├── sales.php
├── users.php
│
├── css/
│   └── style.css
│
├── sari_sari_pos.sql
│
└── README.md