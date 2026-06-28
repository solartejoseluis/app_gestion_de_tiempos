<?php
declare(strict_types=1);

class PlantillaController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $uid     = (int) $_SESSION['usuario_id'];
        $bloques = (new BloqueModel())->getAll($uid);

        $this->layout('plantilla.index', [
            'pageTitle'    => 'Plantilla semanal',
            'currentRoute' => '/plantilla',
            'bloques'      => $bloques,
        ]);
    }

    public function crear(): void
    {
        $this->requireAuth();
        $uid = (int) $_SESSION['usuario_id'];

        $nombre      = trim($this->input('nombre', ''));
        $color       = trim($this->input('color', '#f0c040'));
        $horaInicio  = trim($this->input('hora_inicio', ''));
        $horaFin     = trim($this->input('hora_fin', ''));
        $fechaInicio = trim($this->input('fecha_inicio', '')) ?: null;
        $fechaFin    = trim($this->input('fecha_fin', ''))    ?: null;
        $diasRaw     = trim($this->input('dias_semana', ''));

        if ($nombre === '') {
            $this->error('El nombre es obligatorio.');
        }
        if ($diasRaw === '') {
            $this->error('Selecciona al menos un día.');
        }
        if ($horaInicio === '' || $horaFin === '') {
            $this->error('La hora de inicio y fin son obligatorias.');
        }
        if ($horaInicio >= $horaFin) {
            $this->error('La hora de inicio debe ser menor que la hora de fin.');
        }

        $model = new BloqueModel();
        if ($model->existeNombre($nombre, $uid)) {
            $this->error('Ya existe un bloque con ese nombre.');
        }

        $id = $model->crear([
            'usuario_id'  => $uid,
            'nombre'      => $nombre,
            'color'       => $color,
            'dias_semana' => $diasRaw,
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'estado'      => 'activo',
        ]);

        $this->json([
            'id'          => $id,
            'nombre'      => $nombre,
            'color'       => $color,
            'dias_semana' => $diasRaw,
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'estado'      => 'activo',
        ]);
    }

    public function editar(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        if (!$this->ownsBloque($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        $body = !empty($_POST)
            ? $_POST
            : (array) json_decode(file_get_contents('php://input'), true);

        $campos = [];

        if (isset($body['nombre'])) {
            $nombre = trim($body['nombre']);
            if ($nombre === '') {
                $this->error('El nombre no puede estar vacío.');
            }
            $campos['nombre'] = $nombre;
        }
        if (isset($body['color'])) {
            $campos['color'] = $body['color'];
        }
        if (isset($body['dias_semana'])) {
            $dias = $body['dias_semana'];
            $campos['dias_semana'] = is_array($dias) ? implode(',', $dias) : $dias;
        }
        if (isset($body['hora_inicio'])) {
            $campos['hora_inicio'] = $body['hora_inicio'];
        }
        if (isset($body['hora_fin'])) {
            $campos['hora_fin'] = $body['hora_fin'];
        }
        if (isset($body['fecha_inicio'])) {
            $campos['fecha_inicio'] = trim($body['fecha_inicio']) ?: null;
        }
        if (isset($body['fecha_fin'])) {
            $campos['fecha_fin'] = trim($body['fecha_fin']) ?: null;
        }

        if (!empty($campos)) {
            (new BloqueModel())->actualizar($id, $campos);
        }

        $this->json(null);
    }

    public function archivar(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0 || !$this->ownsBloque($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new BloqueModel())->archivar($id);
        $this->json(null);
    }

    public function restaurar(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0 || !$this->ownsBloque($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new BloqueModel())->restaurar($id);
        $this->json(null);
    }

    public function eliminar(string $id): void
    {
        $this->requireAuth();
        $id  = (int) $id;
        $uid = (int) $_SESSION['usuario_id'];

        if ($id <= 0 || !$this->ownsBloque($id, $uid)) {
            $this->error('No autorizado.', 403);
        }

        (new BloqueModel())->eliminar($id);
        $this->json(null);
    }

    private function ownsBloque(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT id FROM bloques_tiempo
              WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $userId]);
        return (bool) $stmt->fetch();
    }
}
