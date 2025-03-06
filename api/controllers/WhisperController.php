<?php
namespace App\Controllers;

use App\Models\Whisper;
use App\Services\AuthService;
use App\Services\LoggerService;

class WhisperController {
    private $whisperModel;
    private $authService;
    private $logger;

    public function __construct() {
        $this->whisperModel = new Whisper();
        $this->authService = new AuthService();
        $this->logger = LoggerService::getInstance();
    }

    public function create() {
        try {
            // Validar autenticación
            $headers = getallheaders();
            $token = $headers['Authorization'] ?? '';
            
            if (empty($token)) {
                return [
                    'status' => 'error',
                    'message' => 'No autorizado'
                ];
            }

            $tokenData = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$tokenData) {
                return [
                    'status' => 'error',
                    'message' => 'Token inválido'
                ];
            }

            // Obtener datos del susurro
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos requeridos
            if (empty($data['mensaje'])) {
                return [
                    'status' => 'error',
                    'message' => 'El mensaje es requerido'
                ];
            }

            // Validar longitud del mensaje
            if (strlen($data['mensaje']) > 500) {
                return [
                    'status' => 'error',
                    'message' => 'El mensaje excede los 500 caracteres'
                ];
            }

            // Preparar datos para crear el susurro
            $whisperData = [
                'usuario_id' => $tokenData->user_id,
                'mensaje' => $data['mensaje'],
                'etiquetas' => $data['etiquetas'] ?? [],
                'susurro_padre_id' => $data['susurro_padre_id'] ?? null
            ];

            // Crear el susurro
            $whisper_id = $this->whisperModel->create($whisperData);

            return [
                'status' => 'success',
                'message' => 'Susurro creado exitosamente',
                'data' => [
                    'whisper_id' => $whisper_id
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error al crear susurro', [
                'error' => $e->getMessage(),
                'user_id' => $tokenData->user_id ?? null
            ]);

            return [
                'status' => 'error',
                'message' => 'Error al crear el susurro'
            ];
        }
    }

    public function getTags() {
        try {
            $tags = $this->whisperModel->getAllTags();
            return [
                'status' => 'success',
                'data' => [
                    'tags' => $tags
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error al obtener etiquetas', [
                'error' => $e->getMessage()
            ]);
            return [
                'status' => 'error',
                'message' => 'Error al obtener las etiquetas'
            ];
        }
    }

    public function getRecent() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;

            $whispers = $this->whisperModel->getRecent($limit, $offset);
            $totalPages = $this->whisperModel->getTotalPages($limit);

            return [
                'status' => 'success',
                'data' => [
                    'whispers' => $whispers,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'limit' => $limit
                    ]
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error al obtener susurros recientes', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'Error al obtener los susurros'
            ];
        }
    }

    public function getTrending() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 2;
            $offset = ($page - 1) * $limit;

            $whispers = $this->whisperModel->getFeatured($hours, $limit, $offset);

            return [
                'status' => 'success',
                'data' => [
                    'whispers' => $whispers,
                    'pagination' => [
                        'current_page' => $page,
                        'limit' => $limit
                    ]
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error al obtener tendencias', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'Error al obtener las tendencias'
            ];
        }
    }

    public function getPopular() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;

            $whispers = $this->whisperModel->getPopular($limit, $offset);

            return [
                'status' => 'success',
                'data' => [
                    'whispers' => $whispers,
                    'pagination' => [
                        'current_page' => $page,
                        'limit' => $limit
                    ]
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error al obtener populares', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'Error al obtener los populares'
            ];
        }
    }

    public function search() {
        try {
            $searchText = isset($_GET['q']) ? urldecode($_GET['q']) : '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;

            if (empty($searchText)) {
                return [
                    'status' => 'error',
                    'message' => 'Término de búsqueda requerido'
                ];
            }

            // Sanitize search text
            $searchText = trim($searchText);
            $whispers = $this->whisperModel->getByText($searchText, $limit, $offset);
            $totalPages = $this->whisperModel->getTotalPages($limit, null, $searchText);

            return [
                'status' => 'success',
                'data' => [
                    'whispers' => $whispers,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'limit' => $limit
                    ]
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error en búsqueda', [
                'error' => $e->getMessage(),
                'search_text' => $searchText ?? null
            ]);

            return [
                'status' => 'error',
                'message' => 'Error al realizar la búsqueda'
            ];
        }
    }

    public function updateReaction() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['whisper_id']) || !isset($data['type'])) {
                return [
                    'status' => 'error',
                    'message' => 'Datos incompletos'
                ];
            }

            $this->whisperModel->updateReaction($data['whisper_id'], $data['type']);

            return [
                'status' => 'success',
                'message' => 'Reacción actualizada'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error al actualizar reacción', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            return [
                'status' => 'error',
                'message' => 'Error al actualizar la reacción'
            ];
        }
    }
}