<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| App Constants
|--------------------------------------------------------------------------
*/

defined('ROLE_CONSUMER') || define('ROLE_CONSUMER', 'consumer');
defined('ROLE_PRODUCER') || define('ROLE_PRODUCER', 'producer');
defined('ROLE_ADMIN') || define('ROLE_ADMIN', 'admin');

defined('USER_STATUS_ACTIVE') || define('USER_STATUS_ACTIVE', 'active');
defined('USER_STATUS_PENDING') || define('USER_STATUS_PENDING', 'pending');
defined('USER_STATUS_SUSPENDED') || define('USER_STATUS_SUSPENDED', 'suspended');
defined('USER_STATUS_DELETED') || define('USER_STATUS_DELETED', 'deleted');

defined('PRODUCT_STATUS_DRAFT') || define('PRODUCT_STATUS_DRAFT', 'draft');
defined('PRODUCT_STATUS_ACTIVE') || define('PRODUCT_STATUS_ACTIVE', 'active');
defined('PRODUCT_STATUS_SOLD_OUT') || define('PRODUCT_STATUS_SOLD_OUT', 'sold_out');
defined('PRODUCT_STATUS_PAUSED') || define('PRODUCT_STATUS_PAUSED', 'paused');
defined('PRODUCT_STATUS_DELETED') || define('PRODUCT_STATUS_DELETED', 'deleted');

defined('UNIT_KG') || define('UNIT_KG', 'kg');
defined('UNIT_PIECE') || define('UNIT_PIECE', 'piece');
defined('UNIT_BUNCH') || define('UNIT_BUNCH', 'bunch');
defined('UNIT_BOX') || define('UNIT_BOX', 'box');

defined('ORDER_STATUS_PENDING') || define('ORDER_STATUS_PENDING', 'pending');
defined('ORDER_STATUS_CONFIRMED') || define('ORDER_STATUS_CONFIRMED', 'confirmed');
defined('ORDER_STATUS_PREPARING') || define('ORDER_STATUS_PREPARING', 'preparing');
defined('ORDER_STATUS_SHIPPED') || define('ORDER_STATUS_SHIPPED', 'shipped');
defined('ORDER_STATUS_DELIVERED') || define('ORDER_STATUS_DELIVERED', 'delivered');
defined('ORDER_STATUS_CANCELLED') || define('ORDER_STATUS_CANCELLED', 'cancelled');
defined('ORDER_STATUS_REFUNDED') || define('ORDER_STATUS_REFUNDED', 'refunded');

defined('PAYMENT_STATUS_UNPAID') || define('PAYMENT_STATUS_UNPAID', 'unpaid');
defined('PAYMENT_STATUS_PAID') || define('PAYMENT_STATUS_PAID', 'paid');
defined('PAYMENT_STATUS_REFUNDED') || define('PAYMENT_STATUS_REFUNDED', 'refunded');
defined('PAYMENT_STATUS_FAILED') || define('PAYMENT_STATUS_FAILED', 'failed');

defined('PAYMENT_METHOD_VIRTUAL_BALANCE') || define('PAYMENT_METHOD_VIRTUAL_BALANCE', 'virtual_balance');

defined('WALLET_TRANSACTION_DEPOSIT') || define('WALLET_TRANSACTION_DEPOSIT', 'deposit');
defined('WALLET_TRANSACTION_PURCHASE') || define('WALLET_TRANSACTION_PURCHASE', 'purchase');
defined('WALLET_TRANSACTION_REFUND') || define('WALLET_TRANSACTION_REFUND', 'refund');
defined('WALLET_TRANSACTION_PRODUCER_INCOME') || define('WALLET_TRANSACTION_PRODUCER_INCOME', 'producer_income');
defined('WALLET_TRANSACTION_BASKET_PAYMENT') || define('WALLET_TRANSACTION_BASKET_PAYMENT', 'basket_payment');

defined('MAX_UPLOAD_SIZE') || define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);

defined('ALLOWED_IMAGE_MIME_TYPES') || define('ALLOWED_IMAGE_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp',
]);

defined('ALLOWED_IMAGE_EXTENSIONS') || define('ALLOWED_IMAGE_EXTENSIONS', [
    'jpg',
    'jpeg',
    'png',
    'webp',
]);

defined('DEFAULT_PRODUCT_IMAGE') || define('DEFAULT_PRODUCT_IMAGE', 'assets/img/product-placeholder.png');
defined('DEFAULT_PRODUCER_LOGO') || define('DEFAULT_PRODUCER_LOGO', 'assets/img/producer-placeholder.png');