# FoodHub – Online Food Ordering & Restaurant Management (HTML + PHP)

## Kivabe run korben (VS Code)
1. PHP install thakte hobe (PHP 8+). Check: terminal e `php -v`
2. VS Code e `foodhub` folder open korun (File > Open Folder)
3. Terminal (Ctrl + `) e likhun:

       php -S localhost:8000

4. Browser e jan: http://localhost:8000

Database (SQLite) automatic toiri hoy — MySQL / XAMPP lagbe na.

## Demo Login
- Admin: admin@food.com / admin123
- Customer: user@food.com / user123

## Features
- Customer: register/login, menu browse, search, category filter, cart, checkout, order history
- Admin: dashboard stats, order status update, food add/edit/delete, category add

## Database reset korte
`data/app.db` file ta delete korun, abar run korle fresh data toiri hobe.

## Payment System (notun)
Checkout er por customer pay.php te jay ebong 3 ta method pay:
- **Cash on Delivery** – confirm korlei hoy (status: Pay on Delivery)
- **bKash** – customer apnar bKash number e Send Money kore TrxID dey. Admin dashboard theke verify kore "Paid" kore dite hoy
- **Card (demo)** – shudhu demo. Test card: 4242 4242 4242 4242, future expiry, jekono CVV. Real taka kate na, full card number/CVV save hoy na

Apnar bKash number change korte `includes/db.php` er upore `BKASH_NUMBER` edit korun.
Purono database thakle auto upgrade hobe, delete korar dorkar nai.
