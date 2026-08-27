<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Core\Csrf;
use App\Core\Database;
use App\Http\Controllers\BaseController;

final class CollectionPointsHandlerController extends BaseController
{
    private Database $db;

    public function __construct(\App\Core\Container $container)
    {
        parent::__construct($container);
        $this->db = $container->database;
    }

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['admin'];
    }

    public function render(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet();
                    break;

                case 'POST':
                    $this->handlePost();
                    break;

                default:
                    $this->json([
                        'success' => false,
                        'message' => 'Method not allowed',
                    ], 405);
            }
        } catch (\Exception $e) {
            error_log('Collection points API error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }

    private function handleGet(): void
    {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'get_puroks':
                $this->json([
                    'success' => true,
                    'data' => $this->getPuroks(),
                ]);

            case 'get_collection_points':
                $this->json([
                    'success' => true,
                    'data' => $this->getCollectionPoints(),
                ]);

            case 'get_collection_point':
                $point_id = (int) ($_GET['id'] ?? 0);
                if ($point_id <= 0) {
                    $this->json([
                        'success' => false,
                        'message' => 'Collection point ID is required',
                    ], 400);
                }

                $point = $this->getCollectionPoint($point_id);
                if ($point !== null) {
                    $this->json([
                        'success' => true,
                        'data' => $point,
                    ]);
                }

                $this->json([
                    'success' => false,
                    'message' => 'Collection point not found',
                ], 404);

            default:
                $this->json([
                    'success' => false,
                    'message' => 'Invalid action specified',
                ], 400);
        }
    }

    private function handlePost(): void
    {
        $input = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        try {
            Csrf::validate($token);
        } catch (\InvalidArgumentException) {
            $this->json([
                'success' => false,
                'message' => 'Invalid or expired session token.',
            ], 403);
        }

        $action = $input['action'] ?? 'create';

        switch ($action) {
            case 'create':
                $this->json($this->addCollectionPoint($this->sanitizeData($input, [
                    'name', 'purok_id', 'latitude', 'longitude', 'description',
                    'status', 'capacity', 'contact_person', 'contact_phone',
                    'operating_hours', 'notes',
                ])));

            case 'update':
                $this->json($this->updateCollectionPoint($this->sanitizeData($input, [
                    'point_id', 'name', 'purok_id', 'latitude', 'longitude', 'description',
                    'status', 'capacity', 'contact_person', 'contact_phone',
                    'operating_hours', 'notes',
                ])));

            case 'delete':
                $point_id = (int) ($input['point_id'] ?? 0);
                if ($point_id <= 0) {
                    $this->json([
                        'success' => false,
                        'message' => 'Collection point ID is required',
                    ], 400);
                }

                $this->json($this->deleteCollectionPoint($point_id));

            default:
                $this->json([
                    'success' => false,
                    'message' => 'Invalid action specified',
                ], 400);
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    private function sanitizeData(array $input, array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            if (in_array($field, ['point_id', 'purok_id'], true)) {
                $data[$field] = (int) $input[$field];
            } elseif (in_array($field, ['latitude', 'longitude', 'capacity'], true)) {
                $data[$field] = $input[$field] !== '' && is_numeric($input[$field])
                    ? (float) $input[$field]
                    : null;
            } else {
                $data[$field] = $this->sanitizeInput((string) $input[$field]);
            }
        }

        return $data;
    }

    private function sanitizeInput(string $data): string
    {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function validateCoordinates(float $lat, float $lng): bool
    {
        return $lat >= 4 && $lat <= 21 && $lng >= 116 && $lng <= 127;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getPuroks(): array
    {
        try {
            return $this->db->query('SELECT purok_id, name FROM puroks ORDER BY name');
        } catch (\Exception $e) {
            error_log('Error fetching puroks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getCollectionPoints(): array
    {
        try {
            $points = $this->db->query(
                'SELECT cp.*, p.name as purok_name, LOWER(cp.status) as status_lower, cp.capacity_kg as capacity
                 FROM collection_points cp
                 LEFT JOIN puroks p ON cp.purok_id = p.purok_id
                 ORDER BY cp.name',
            );

            foreach ($points as &$point) {
                $point['status'] = $point['status_lower'] ?? $point['status'];
                unset($point['status_lower']);
            }

            return $points;
        } catch (\Exception $e) {
            error_log('Error fetching collection points: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCollectionPoint(int $point_id): ?array
    {
        try {
            $point = $this->db->selectOne(
                'SELECT cp.*, p.name as purok_name, LOWER(cp.status) as status_lower, cp.capacity_kg as capacity
                 FROM collection_points cp
                 LEFT JOIN puroks p ON cp.purok_id = p.purok_id
                 WHERE cp.point_id = ?',
                [$point_id],
                'i',
            );

            if ($point !== null) {
                $point['status'] = $point['status_lower'] ?? $point['status'];
                unset($point['status_lower']);
            }

            return $point;
        } catch (\Exception $e) {
            error_log('Error fetching collection point: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function updateCollectionPoint(array $data): array
    {
        try {
            $required_fields = ['point_id', 'name', 'purok_id', 'latitude', 'longitude', 'status'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Field '{$field}' is required");
                }
            }

            if (!$this->validateCoordinates((float) $data['latitude'], (float) $data['longitude'])) {
                throw new \Exception('Invalid coordinates. Please ensure coordinates are within the Philippines.');
            }

            $purok = $this->db->selectOne('SELECT purok_id FROM puroks WHERE purok_id = ?', [$data['purok_id']], 'i');
            if ($purok === null) {
                throw new \Exception('Invalid purok selected');
            }

            $existing = $this->db->selectOne(
                'SELECT point_id FROM collection_points WHERE point_id = ?',
                [$data['point_id']],
                'i',
            );
            if ($existing === null) {
                throw new \Exception('Collection point not found');
            }

            $name_check = $this->db->selectOne(
                'SELECT point_id FROM collection_points WHERE name = ? AND purok_id = ? AND point_id != ?',
                [$data['name'], $data['purok_id'], $data['point_id']],
                'sii',
            );
            if ($name_check !== null) {
                throw new \Exception('A collection point with this name already exists in the selected purok');
            }

            $capacity_value = !empty($data['capacity']) ? (float) $data['capacity'] : 0.0;

            $this->db->execute(
                'UPDATE collection_points SET
                    name = ?, purok_id = ?, latitude = ?, longitude = ?, description = ?,
                    status = ?, capacity_kg = ?, contact_person = ?, contact_phone = ?,
                    operating_hours = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE point_id = ?',
                [
                    $data['name'],
                    $data['purok_id'],
                    $data['latitude'],
                    $data['longitude'],
                    $data['description'] ?? '',
                    ucfirst((string) $data['status']),
                    $capacity_value,
                    $data['contact_person'] ?? '',
                    $data['contact_phone'] ?? '',
                    $data['operating_hours'] ?? '24/7',
                    $data['notes'] ?? '',
                    $data['point_id'],
                ],
                'siddssdssssi',
            );

            return [
                'success' => true,
                'message' => 'Collection point updated successfully!',
            ];
        } catch (\Exception $e) {
            error_log('Error updating collection point: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function addCollectionPoint(array $data): array
    {
        try {
            $required_fields = ['name', 'purok_id', 'latitude', 'longitude', 'status'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Field '{$field}' is required");
                }
            }

            if (!$this->validateCoordinates((float) $data['latitude'], (float) $data['longitude'])) {
                throw new \Exception('Invalid coordinates. Please ensure coordinates are within the Philippines.');
            }

            $purok = $this->db->selectOne('SELECT purok_id FROM puroks WHERE purok_id = ?', [$data['purok_id']], 'i');
            if ($purok === null) {
                throw new \Exception('Invalid purok selected');
            }

            $name_check = $this->db->selectOne(
                'SELECT point_id FROM collection_points WHERE name = ? AND purok_id = ?',
                [$data['name'], $data['purok_id']],
                'si',
            );
            if ($name_check !== null) {
                throw new \Exception('A collection point with this name already exists in the selected purok');
            }

            $capacity_value = !empty($data['capacity']) ? (float) $data['capacity'] : 0.0;

            $point_id = $this->db->insert(
                'INSERT INTO collection_points (
                    name, purok_id, latitude, longitude, description, status,
                    capacity_kg, contact_person, contact_phone, operating_hours, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['name'],
                    $data['purok_id'],
                    $data['latitude'],
                    $data['longitude'],
                    $data['description'] ?? '',
                    ucfirst((string) $data['status']),
                    $capacity_value,
                    $data['contact_person'] ?? '',
                    $data['contact_phone'] ?? '',
                    $data['operating_hours'] ?? '24/7',
                    $data['notes'] ?? '',
                ],
                'siddssdssss',
            );

            return [
                'success' => true,
                'message' => 'Collection point added successfully!',
                'point_id' => $point_id,
            ];
        } catch (\Exception $e) {
            error_log('Error adding collection point: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function deleteCollectionPoint(int $point_id): array
    {
        try {
            $point = $this->db->selectOne(
                'SELECT point_id, name FROM collection_points WHERE point_id = ?',
                [$point_id],
                'i',
            );
            if ($point === null) {
                throw new \Exception('Collection point not found');
            }

            $requests = $this->db->selectOne(
                'SELECT COUNT(*) as count FROM pickuprequests WHERE point_id = ?',
                [$point_id],
                'i',
            );
            $count = (int) ($requests['count'] ?? 0);
            if ($count > 0) {
                throw new \Exception("Cannot delete collection point. There are {$count} pickup requests associated with this location.");
            }

            $this->db->execute('DELETE FROM collection_points WHERE point_id = ?', [$point_id], 'i');

            return [
                'success' => true,
                'message' => "Collection point '{$point['name']}' deleted successfully!",
            ];
        } catch (\Exception $e) {
            error_log('Error deleting collection point: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
