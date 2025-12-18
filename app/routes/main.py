from flask import Blueprint, render_template, redirect, url_for, request, flash
from flask_login import login_required, current_user
from app import db
from app.models import Product, Transaction
from datetime import datetime

bp = Blueprint('main', __name__)

@bp.route('/')
def index():
    return redirect(url_for('auth.login'))

@bp.route('/dashboard')
@login_required
def dashboard():
    return redirect(url_for('main.inventory'))

@bp.route('/inventory')
@login_required
def inventory():
    search_query = request.args.get('search', '').strip()
    
    if search_query:
        # Search by name or ID (sku)
        products = Product.query.filter(
            db.or_(
                Product.name.ilike(f'%{search_query}%'),
                Product.sku.ilike(f'%{search_query}%')
            )
        ).order_by(Product.name).all()
    else:
        products = Product.query.order_by(Product.name).all()
    
    return render_template('inventory.html', products=products, search_query=search_query)

@bp.route('/product/add', methods=['GET', 'POST'])
@login_required
def add_product():
    if request.method == 'POST':
        product = Product(
            name=request.form.get('name'),
            sku=request.form.get('sku'),
            description=request.form.get('description'),
            quantity=1,
            unit='stk',
            location=request.form.get('location'),
            is_available=True
        )
        
        try:
            db.session.add(product)
            db.session.commit()
            flash('Product added successfully', 'success')
            return redirect(url_for('main.inventory'))
        except Exception as e:
            db.session.rollback()
            flash(f'Error adding product: {str(e)}', 'danger')
    
    return render_template('add_product.html')

@bp.route('/product/<int:id>/edit', methods=['GET', 'POST'])
@login_required
def edit_product(id):
    product = Product.query.get_or_404(id)
    
    if request.method == 'POST':
        product.name = request.form.get('name')
        product.sku = request.form.get('sku')
        product.description = request.form.get('description')
        product.location = request.form.get('location')
        product.updated_at = datetime.utcnow()
        
        try:
            db.session.commit()
            flash('Product updated successfully', 'success')
            return redirect(url_for('main.inventory'))
        except Exception as e:
            db.session.rollback()
            flash(f'Error updating product: {str(e)}', 'danger')
    
    return render_template('edit_product.html', product=product)

@bp.route('/product/<int:id>/delete', methods=['POST'])
@login_required
def delete_product(id):
    product = Product.query.get_or_404(id)
    
    try:
        db.session.delete(product)
        db.session.commit()
        flash('Product deleted successfully', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Error deleting product: {str(e)}', 'danger')
    
    return redirect(url_for('main.inventory'))

@bp.route('/product/<int:id>/use', methods=['GET', 'POST'])
@login_required
def use_product(id):
    product = Product.query.get_or_404(id)
    
    if request.method == 'POST':
        borrower_name = request.form.get('borrower_name')
        from_date_str = request.form.get('from_date')
        to_date_str = request.form.get('to_date')
        notes = request.form.get('notes')
        
        # Parse dates
        from_date = datetime.strptime(from_date_str, '%Y-%m-%dT%H:%M')
        to_date = datetime.strptime(to_date_str, '%Y-%m-%dT%H:%M')
        
        if to_date <= from_date:
            flash('End time must be after start time', 'danger')
            return redirect(url_for('main.use_product', id=id))
        
        # Create checkout transaction
        transaction = Transaction(
            product_id=product.id,
            user_id=current_user.id,
            borrower_name=borrower_name,
            from_date=from_date,
            to_date=to_date,
            notes=notes,
            status='active'
        )
        
        # Mark product as unavailable
        product.is_available = False
        product.updated_at = datetime.utcnow()
        
        try:
            db.session.add(transaction)
            db.session.commit()
            flash(f'Item checked out to {borrower_name}', 'success')
            return redirect(url_for('main.inventory'))
        except Exception as e:
            db.session.rollback()
            flash(f'Error checking out item: {str(e)}', 'danger')
    
    return render_template('use_product.html', product=product)

@bp.route('/product/<int:id>/info')
@login_required
def product_info(id):
    product = Product.query.get_or_404(id)
    active_checkout = Transaction.query.filter_by(
        product_id=id, 
        status='active'
    ).first()
    checkout_history = Transaction.query.filter_by(
        product_id=id
    ).order_by(Transaction.checked_out_at.desc()).all()
    
    return render_template('product_info.html', 
                         product=product,
                         active_checkout=active_checkout,
                         checkout_history=checkout_history)

@bp.route('/product/<int:id>/return', methods=['POST'])
@login_required
def return_product(id):
    product = Product.query.get_or_404(id)
    active_checkout = Transaction.query.filter_by(
        product_id=id,
        status='active'
    ).first()
    
    if active_checkout:
        active_checkout.status = 'returned'
        active_checkout.returned_at = datetime.utcnow()
        product.is_available = True
        product.updated_at = datetime.utcnow()
        
        try:
            db.session.commit()
            flash('Item returned successfully', 'success')
        except Exception as e:
            db.session.rollback()
            flash(f'Error returning item: {str(e)}', 'danger')
    else:
        flash('No active checkout found', 'warning')
    
    return redirect(url_for('main.inventory'))

@bp.route('/checkouts')
@login_required
def checkouts():
    page = request.args.get('page', 1, type=int)
    checkouts = Transaction.query.order_by(Transaction.checked_out_at.desc()).paginate(
        page=page, per_page=20, error_out=False
    )
    return render_template('checkouts.html', checkouts=checkouts)
