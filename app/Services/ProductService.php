<?php

class ProductService
{
    public function getCategories(): array
    {
        return Category::allActive();
    }

    public function getProducerProducts(int $producerId): array
    {
        if ($producerId <= 0) {
            return [];
        }

        return Product::getByProducerId($producerId);
    }

    public function searchProducts(array $filters = []): array
    {
        $normalizedFilters = [
            'q' => trim($filters['q'] ?? ''),
            'category_id' => !empty($filters['category_id']) ? (int) $filters['category_id'] : null,
            'province_id' => !empty($filters['province_id']) ? (int) $filters['province_id'] : null,
            'district_id' => !empty($filters['district_id']) ? (int) $filters['district_id'] : null,
            'min_price' => $filters['min_price'] ?? '',
            'max_price' => $filters['max_price'] ?? '',
            'sort' => $filters['sort'] ?? 'newest',
            'in_stock' => !empty($filters['in_stock']),
            'preorder' => !empty($filters['preorder']),

            // Ürün kartında kalbin dolu/boş görünmesi için.
            'favorite_user_id' => (isLoggedIn() && isConsumer()) ? (int) currentUserId() : 0,
        ];

        return Product::search($normalizedFilters);
    }

    public function getProductDetail(int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        $product = Product::findById($productId);

        if (!$product) {
            return null;
        }

        Product::incrementViewCount($productId);

        $product['images'] = ProductImage::getByProductId($productId);
        $product['cover_image'] = ProductImage::getCoverByProductId($productId);

        // Ürün detay sayfasında kalp ikonunun dolu/boş görünmesi için.
        $product['is_favorited'] = (isLoggedIn() && isConsumer() && class_exists('Favorite'))
            ? Favorite::isFavorited((int) currentUserId(), $productId)
            : false;

        $product['reviews'] = class_exists('Review')
            ? Review::getVisibleByProductId($productId)
            : [];

        return $product;
    }

    public function createProduct(int $producerId, array $data, array $files = []): array
    {
        $data = $this->normalizeProductData($data);
        $data['producer_id'] = $producerId;

        $errors = $this->validateProductData($data);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        try {
            $productId = db_transaction(function () use ($data, $files) {
                $productId = Product::create([
                    'producer_id' => $data['producer_id'],
                    'category_id' => $data['category_id'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'unit_type' => $data['unit_type'],
                    'price' => $data['price'],
                    'stock_quantity' => $data['stock_quantity'],
                    'harvest_date' => $data['harvest_date'],
                    'is_preorder_enabled' => $data['is_preorder_enabled'],
                    'preorder_deadline' => $data['preorder_deadline'],
                    'min_preorder_quantity' => $data['min_preorder_quantity'],
                    'min_preorder_unit' => $data['min_preorder_unit'],
                    'status' => $data['status'],
                ]);

                $this->handleProductImageUpload($productId, $files);

                if ($data['stock_quantity'] > 0) {
                    $this->recordInventoryMovement(
                        $productId,
                        'initial',
                        $data['stock_quantity'],
                        null,
                        'İlk stok kaydı'
                    );
                }

                return $productId;
            });

            return [
                'success' => true,
                'message' => 'Ürün başarıyla oluşturuldu.',
                'product_id' => $productId,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'errors' => [
                    'general' => ['Ürün oluşturulurken bir hata oluştu.'],
                ],
            ];
        }
    }

    public function updateProduct(int $producerId, int $productId, array $data, array $files = []): array
    {
        if ($productId <= 0) {
            return [
                'success' => false,
                'errors' => [
                    'general' => ['Geçerli bir ürün ID değeri bulunamadı.'],
                ],
            ];
        }

        $existingProduct = Product::findById($productId);

        if (!$existingProduct || (int) $existingProduct['producer_id'] !== $producerId) {
            return [
                'success' => false,
                'errors' => [
                    'general' => ['Bu ürünü düzenleme yetkiniz yok.'],
                ],
            ];
        }

        $data = $this->normalizeProductData($data);
        $data['producer_id'] = $producerId;

        $errors = $this->validateProductData($data, true);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        try {
            db_transaction(function () use ($productId, $producerId, $data, $files, $existingProduct) {
                Product::update($productId, $producerId, [
                    'category_id' => $data['category_id'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'unit_type' => $data['unit_type'],
                    'price' => $data['price'],
                    'stock_quantity' => $data['stock_quantity'],
                    'harvest_date' => $data['harvest_date'],
                    'is_preorder_enabled' => $data['is_preorder_enabled'],
                    'preorder_deadline' => $data['preorder_deadline'],
                    'min_preorder_quantity' => $data['min_preorder_quantity'],
                    'min_preorder_unit' => $data['min_preorder_unit'],
                    'status' => $data['status'],
                ]);

                $this->handleProductImageUpload($productId, $files);

                $oldStock = (float) ($existingProduct['stock_quantity'] ?? 0);
                $newStock = (float) $data['stock_quantity'];
                $difference = $newStock - $oldStock;

                if ($difference !== 0.0) {
                    $this->recordInventoryMovement(
                        $productId,
                        $difference > 0 ? 'restock' : 'correction',
                        $difference,
                        null,
                        'Ürün düzenleme stok güncellemesi'
                    );
                }
            });

            return [
                'success' => true,
                'message' => 'Ürün başarıyla güncellendi.',
                'product_id' => $productId,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'errors' => [
                    'general' => ['Ürün güncellenirken bir hata oluştu.'],
                ],
            ];
        }
    }

    public function deleteProduct(int $producerId, int $productId): array
    {
        if ($productId <= 0) {
            return [
                'success' => false,
                'message' => 'Geçerli bir ürün ID değeri gönderilmelidir.',
            ];
        }

        $product = Product::findById($productId);

        if (!$product || (int) $product['producer_id'] !== $producerId) {
            return [
                'success' => false,
                'message' => 'Bu ürünü silme yetkiniz yok.',
            ];
        }

        Product::softDelete($productId, $producerId);

        return [
            'success' => true,
            'message' => 'Ürün silindi.',
        ];
    }

    public function changeStatus(int $producerId, int $productId, string $status): array
    {
        if ($productId <= 0) {
            return [
                'success' => false,
                'message' => 'Geçerli bir ürün ID değeri gönderilmelidir.',
            ];
        }

        $product = Product::findById($productId);

        if (!$product || (int) $product['producer_id'] !== $producerId) {
            return [
                'success' => false,
                'message' => 'Bu ürünün durumunu değiştirme yetkiniz yok.',
            ];
        }

        try {
            Product::updateStatus($productId, $producerId, $status);

            return [
                'success' => true,
                'message' => 'Ürün durumu güncellendi.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Ürün durumu güncellenemedi.',
            ];
        }
    }

    private function normalizeProductData(array $data): array
    {
        return [
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : 0,
            'title' => trim($data['title'] ?? ''),
            'description' => trim($data['description'] ?? '') ?: null,
            'unit_type' => $data['unit_type'] ?? UNIT_KG,
            'price' => isset($data['price']) ? (float) str_replace(',', '.', (string) $data['price']) : 0,
            'stock_quantity' => isset($data['stock_quantity']) ? (float) str_replace(',', '.', (string) $data['stock_quantity']) : 0,
            'harvest_date' => trim($data['harvest_date'] ?? '') ?: null,
            'is_preorder_enabled' => !empty($data['is_preorder_enabled']),
            'preorder_deadline' => trim($data['preorder_deadline'] ?? '') ?: null,
            'min_preorder_quantity' => isset($data['min_preorder_quantity']) && $data['min_preorder_quantity'] !== ''
                ? (float) str_replace(',', '.', (string) $data['min_preorder_quantity'])
                : null,
            'min_preorder_unit' => $data['min_preorder_unit'] ?? UNIT_KG,
            'status' => $data['status'] ?? PRODUCT_STATUS_ACTIVE,
        ];
    }

    private function validateProductData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (empty($data['category_id'])) {
            $errors['category_id'][] = 'Kategori seçimi zorunludur.';
        } elseif (!Category::findById((int) $data['category_id'])) {
            $errors['category_id'][] = 'Seçilen kategori bulunamadı.';
        }

        if ($data['title'] === '') {
            $errors['title'][] = 'Ürün adı zorunludur.';
        } elseif (mb_strlen($data['title'], 'UTF-8') < 2) {
            $errors['title'][] = 'Ürün adı en az 2 karakter olmalıdır.';
        }

        $allowedUnits = [
            UNIT_KG,
            UNIT_PIECE,
            UNIT_BUNCH,
            UNIT_BOX,
        ];

        if (!in_array($data['unit_type'], $allowedUnits, true)) {
            $errors['unit_type'][] = 'Geçerli bir ürün birimi seçmelisiniz.';
        }

        if ($data['price'] <= 0) {
            $errors['price'][] = 'Ürün fiyatı 0’dan büyük olmalıdır.';
        }

        if ($data['stock_quantity'] < 0) {
            $errors['stock_quantity'][] = 'Stok miktarı negatif olamaz.';
        }

        $allowedStatuses = [
            PRODUCT_STATUS_DRAFT,
            PRODUCT_STATUS_ACTIVE,
            PRODUCT_STATUS_SOLD_OUT,
            PRODUCT_STATUS_PAUSED,
        ];

        if (!in_array($data['status'], $allowedStatuses, true)) {
            $errors['status'][] = 'Geçerli bir ürün durumu seçmelisiniz.';
        }

        $allowedPreorderUnits = [UNIT_KG, 'g', UNIT_PIECE];

        if (!in_array($data['min_preorder_unit'], $allowedPreorderUnits, true)) {
            $errors['min_preorder_unit'][] = 'Minimum ön sipariş birimi kg, g veya adet olmalıdır.';
        }

        $today = new DateTimeImmutable('today');
        $oneYearAgo = $today->modify('-1 year');
        $oneYearAfter = $today->modify('+1 year');
        $harvestDate = null;

        if ($data['harvest_date']) {
            $harvestDate = DateTimeImmutable::createFromFormat('!Y-m-d', $data['harvest_date']) ?: null;

            if (!$harvestDate) {
                $errors['harvest_date'][] = 'Hasat tarihi geçerli bir tarih olmalıdır.';
            } elseif ($data['is_preorder_enabled']) {
                if ($harvestDate < $today || $harvestDate > $oneYearAfter) {
                    $errors['harvest_date'][] = 'Ön sipariş ürünlerinde hasat tarihi bugünden itibaren en fazla 1 yıl sonrası olabilir.';
                }
            } else {
                if ($harvestDate < $oneYearAgo || $harvestDate > $today) {
                    $errors['harvest_date'][] = 'Normal ürünlerde hasat tarihi bugünden en fazla 1 yıl önce ve en fazla bugün olabilir.';
                }
            }
        }

        if ($data['is_preorder_enabled']) {
            if (!$data['harvest_date']) {
                $errors['harvest_date'][] = 'Ön sipariş ürünlerinde tahmini hasat tarihi girilmelidir.';
            }

            if (!$data['preorder_deadline']) {
                $errors['preorder_deadline'][] = 'Ön sipariş açıkken son tarih girilmelidir.';
            } else {
                $deadline = DateTimeImmutable::createFromFormat('!Y-m-d', $data['preorder_deadline']) ?: null;

                if (!$deadline) {
                    $errors['preorder_deadline'][] = 'Ön sipariş son tarihi geçerli bir tarih olmalıdır.';
                } elseif ($deadline < $today || $deadline > $oneYearAfter) {
                    $errors['preorder_deadline'][] = 'Ön sipariş son tarihi bugünden itibaren en fazla 1 yıl sonrası olabilir.';
                } elseif ($harvestDate && $deadline > $harvestDate) {
                    $errors['preorder_deadline'][] = 'Ön sipariş son tarihi hasat tarihinden sonra olamaz.';
                }
            }

            if ($data['min_preorder_quantity'] === null || $data['min_preorder_quantity'] <= 0) {
                $errors['min_preorder_quantity'][] = 'Ön sipariş açıkken minimum ön sipariş miktarı 0’dan büyük olmalıdır.';
            }
        } else {
            // Ön sipariş kapalıysa bu alanlar yanlışlıkla POST edilse bile sorun çıkarmasın.
            if ($data['preorder_deadline'] && DateTimeImmutable::createFromFormat('!Y-m-d', $data['preorder_deadline']) === false) {
                $errors['preorder_deadline'][] = 'Ön sipariş son tarihi geçerli bir tarih olmalıdır.';
            }

            if ($data['min_preorder_quantity'] !== null && $data['min_preorder_quantity'] <= 0) {
                $errors['min_preorder_quantity'][] = 'Minimum ön sipariş miktarı 0’dan büyük olmalıdır.';
            }
        }

        return $errors;
    }

    private function handleProductImageUpload(int $productId, array $files): void
    {
        if (empty($files['image']) || ($files['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $uploadedPath = upload_image($files['image'], 'products');

        if (!$uploadedPath) {
            throw new RuntimeException('Ürün fotoğrafı yüklenemedi.');
        }

        $imageCount = ProductImage::countByProductId($productId);

        ProductImage::create([
            'product_id' => $productId,
            'image_path' => $uploadedPath,
            'sort_order' => $imageCount,
            'is_cover' => $imageCount === 0,
        ]);
    }

    private function recordInventoryMovement(
        int $productId,
        string $movementType,
        float $quantity,
        ?int $orderItemId = null,
        ?string $note = null
    ): void {
        try {
            $stmt = db()->prepare("
                INSERT INTO product_inventory_movements (
                    product_id,
                    movement_type,
                    quantity,
                    order_item_id,
                    note
                ) VALUES (
                    :product_id,
                    :movement_type,
                    :quantity,
                    :order_item_id,
                    :note
                )
            ");

            $stmt->execute([
                'product_id' => $productId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'order_item_id' => $orderItemId,
                'note' => $note,
            ]);
        } catch (Throwable $e) {
            /*
             * Hackathon güvenliği:
             * product_inventory_movements tablosu henüz kurulmamışsa ürün oluşturma
             * işlemini bozmamak için stok hareket kaydı sessiz geçilir.
             */
        }
    }
}
