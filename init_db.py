from app import create_app, db
from app.models import User

app = create_app()

def init_db():
    with app.app_context():
        # Create all tables
        db.create_all()
        
        # Check if admin user already exists
        admin = User.query.filter_by(username='admin').first()
        if not admin:
            # Create default admin user
            admin = User(username='admin', email='admin@warehouse.local', is_admin=True)
            admin.set_password('admin123')
            db.session.add(admin)
            db.session.commit()
            print('✅ Database initialized!')
            print('🔑 Default admin user created:')
            print('   Username: admin')
            print('   Password: admin123')
            print('⚠️  Please change the password after first login!')
        else:
            print('ℹ️  Database already initialized')

if __name__ == '__main__':
    init_db()
