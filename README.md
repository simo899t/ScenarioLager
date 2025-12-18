# Warehouse Management System

A simple, secure warehouse inventory management system for small companies with web-based access and authentication.

## Features

- 🔐 **Secure Authentication** - Username/password login with session management
- 📦 **Inventory Management** - Track products, quantities, and locations
- 📊 **Dashboard** - Quick overview of inventory status and recent activity
- 📝 **Transaction History** - Record and track stock in/out movements
- ⚠️ **Low Stock Alerts** - Visual indicators for products below minimum quantity
- 👥 **User Management** - Multiple users with individual tracking

## Technology Stack

- **Backend**: Python Flask
- **Database**: SQLite
- **Authentication**: Flask-Login with password hashing
- **Frontend**: HTML templates with responsive CSS

## Installation

### Prerequisites

- Python 3.8 or higher
- pip (Python package manager)

### Setup Instructions

1. **Create a virtual environment** (recommended):
```bash
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

2. **Install dependencies**:
```bash
pip install -r requirements.txt
```

3. **Initialize the database**:
```bash
python init_db.py
```

This will create the database and a default admin account:
- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Important**: Change the default password after first login!

4. **Run the application**:
```bash
python run.py
```

5. **Access the system**:
Open your web browser and navigate to:
```
http://localhost:5000
```

## Usage

### First Time Setup

1. Log in with the default credentials (admin/admin123)
2. Go to Settings (if implemented) to change your password
3. Start adding products to your inventory

### Adding Products

1. Navigate to **Inventory** → **Add Product**
2. Fill in:
   - Product name
   - SKU (unique identifier)
   - Initial quantity
   - Minimum quantity (for low stock alerts)
   - Location (optional)
   - Unit of measurement (pcs, kg, boxes, etc.)

### Recording Transactions

1. Go to **New Transaction**
2. Select the product
3. Choose transaction type:
   - **Stock In** - Receiving new inventory
   - **Stock Out** - Shipping or removing inventory
4. Enter quantity and optional notes
5. Submit to update inventory

### Monitoring Inventory

- **Dashboard**: View total products and low stock items
- **Inventory**: See all products with current quantities and status
- **Transaction History**: Review all past inventory movements

## Security Notes

- Passwords are hashed using Werkzeug's security functions
- Sessions are managed securely with Flask-Login
- Default secret key should be changed for production use
- Access to all pages (except login) requires authentication

## Production Deployment

For production use:

1. **Change the secret key** in config.py:
```python
SECRET_KEY = 'your-secure-random-key-here'
```

2. **Use a production WSGI server** (e.g., Gunicorn):
```bash
pip install gunicorn
gunicorn -w 4 -b 0.0.0.0:5000 run:app
```

3. **Set up HTTPS** using a reverse proxy (nginx/Apache)

4. **Configure proper database backups**

5. **Consider upgrading to PostgreSQL** for better performance with multiple users

## File Structure

```
ScenarioLager/
├── app/
│   ├── __init__.py          # Flask app initialization
│   ├── models.py            # Database models
│   ├── routes/
│   │   ├── auth.py          # Authentication routes
│   │   └── main.py          # Main application routes
│   ├── templates/           # HTML templates
│   │   ├── base.html
│   │   ├── login.html
│   │   ├── dashboard.html
│   │   ├── inventory.html
│   │   ├── add_product.html
│   │   ├── edit_product.html
│   │   ├── add_transaction.html
│   │   └── transactions.html
│   └── static/
│       └── style.css        # Stylesheet
├── config.py                # Configuration
├── init_db.py              # Database initialization
├── run.py                  # Application entry point
├── requirements.txt        # Python dependencies
└── README.md              # This file
```

## Database Schema

### Users
- id, username, password_hash, email, is_admin, created_at

### Products
- id, name, sku, description, quantity, min_quantity, unit, location, created_at, updated_at

### Transactions
- id, product_id, user_id, transaction_type (in/out), quantity, notes, timestamp

## Adding New Users

To add new users, you can extend the system by:
1. Creating an admin panel (future feature)
2. Using Python shell:

```python
from app import create_app, db
from app.models import User

app = create_app()
with app.app_context():
    admin = User.query.filter_by(username='admin').first()
    admin.set_password('YourNewSecurePassword123!')
    db.session.commit()
```

## License

This project is created for internal use. Modify as needed for your organization.

## Support

For issues or questions, please contact your system administrator.
