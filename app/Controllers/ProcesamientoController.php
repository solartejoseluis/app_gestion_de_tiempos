<?php
declare(strict_types=1);

class ProcesamientoController extends Controller
{
    // ── Helpers privados ─────────────────────────────────────────────────────

    private function uid(): int
    {
        return (int) $_SESSION['usuario_id'];
    }

    private function itemId(): int
    {
        $id = (int) $this->input('id', 0);
        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        return $id;
    }

    private function verificarItem(int $id): bool
    {
        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id FROM items WHERE id = ? AND usuario_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id, $this->uid()]);
        return (bool) $stmt->fetch();
    }

    private function assertItem(int $id): void
    {
        if (!$this->verificarItem($id)) {
            $this->error('No autorizado.', 403);
        }
    }

    private function actualizarItem(int $id, array $campos): void
    {
        $db   = Database::connection();
        $sets = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($campos)));
        $db->prepare("UPDATE items SET {$sets} WHERE id = ?")
           ->execute([...array_values($campos), $id]);
    }

    private function tipoTiempoValido(string $valor): string
    {
        return in_array($valor, ['ninguno', 'dia', 'cita'], true) ? $valor : 'ninguno';
    }

    private function fechaONull(string $key): ?string
    {
        $v = trim($this->input($key, ''));
        return $v !== '' ? $v : null;
    }

    private function intONull(string $key): ?int
    {
        $v = (int) $this->input($key, 0);
        return $v > 0 ? $v : null;
    }

    // ── Catálogos ─────────────────────────────────────────────────────────────

    public function areas(): void
    {
        $this->requireAuth();
        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id, nombre FROM areas
             WHERE usuario_id = ? AND deleted_at IS NULL
             ORDER BY nombre'
        );
        $stmt->execute([$this->uid()]);
        $this->json($stmt->fetchAll());
    }

    public function proyectos(): void
    {
        $this->requireAuth();
        $db     = Database::connection();
        $areaId = $this->intONull('area_id');

        if ($areaId !== null) {
            $stmt = $db->prepare(
                'SELECT id, nombre FROM proyectos
                 WHERE usuario_id = ? AND area_id = ? AND estado = ? AND deleted_at IS NULL
                 ORDER BY nombre'
            );
            $stmt->execute([$this->uid(), $areaId, 'activo']);
        } else {
            $stmt = $db->prepare(
                'SELECT id, nombre FROM proyectos
                 WHERE usuario_id = ? AND estado = ? AND deleted_at IS NULL
                 ORDER BY nombre'
            );
            $stmt->execute([$this->uid(), 'activo']);
        }

        $this->json($stmt->fetchAll());
    }

    public function personas(): void
    {
        $this->requireAuth();
        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id, nombre FROM personas
             WHERE usuario_id = ? AND deleted_at IS NULL
             ORDER BY nombre'
        );
        $stmt->execute([$this->uid()]);
        $this->json($stmt->fetchAll());
    }

    public function contextos(): void
    {
        $this->requireAuth();
        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT id, nombre FROM contextos
             WHERE usuario_id = ? AND deleted_at IS NULL
             ORDER BY nombre'
        );
        $stmt->execute([$this->uid()]);
        $this->json($stmt->fetchAll());
    }

    // ── Acciones ──────────────────────────────────────────────────────────────

    public function eliminar(): void
    {
        $this->requireAuth();
        $id = $this->itemId();
        $this->assertItem($id);

        $this->actualizarItem($id, [
            'tipo'       => 'eliminada',
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(null);
    }

    public function completar(): void
    {
        $this->requireAuth();
        $id = $this->itemId();
        $this->assertItem($id);

        $this->actualizarItem($id, [
            'tipo'            => 'completada',
            'fecha_completada' => date('Y-m-d H:i:s'),
        ]);
        $this->json(null);
    }

    public function incubar(): void
    {
        $this->requireAuth();
        $id = $this->itemId();
        $this->assertItem($id);

        $campos = ['tipo' => 'incubada'];

        $proyectoId    = $this->intONull('proyecto_id');
        $fechaRevision = $this->fechaONull('fecha_revision');

        if ($proyectoId !== null)    $campos['proyecto_id']   = $proyectoId;
        if ($fechaRevision !== null) $campos['fecha_revision'] = $fechaRevision;

        $this->actualizarItem($id, $campos);
        $this->json(null);
    }

    public function referencia(): void
    {
        $this->requireAuth();
        $id = $this->itemId();
        $this->assertItem($id);

        $campos = ['tipo' => 'referencia'];

        $proyectoId = $this->intONull('proyecto_id');
        if ($proyectoId !== null) $campos['proyecto_id'] = $proyectoId;

        $etiquetas = trim($this->input('etiquetas', ''));
        if ($etiquetas !== '') {
            // Normaliza a CSV sin espacios alrededor de las comas
            $campos['etiquetas'] = implode(',', array_map(
                'trim',
                explode(',', $etiquetas)
            ));
        }

        $this->actualizarItem($id, $campos);
        $this->json(null);
    }

    public function programar(): void
    {
        $this->requireAuth();
        $id = $this->itemId();
        $this->assertItem($id);

        $contextoId = (int) $this->input('contexto_id', 0);
        if ($contextoId <= 0) {
            $this->error('El contexto es obligatorio.');
        }

        $proyectoId = $this->intONull('proyecto_id');
        $tipoTiempo = $this->tipoTiempoValido($this->input('tipo_tiempo', 'ninguno'));
        $fechaAccion = $this->fechaONull('fecha_accion');

        $campos = [
            'tipo'        => $proyectoId !== null ? 'proyecto_accion' : 'accion',
            'contexto_id' => $contextoId,
            'tipo_tiempo' => $tipoTiempo,
            'fecha_accion' => $fechaAccion,
        ];

        if ($proyectoId !== null) $campos['proyecto_id'] = $proyectoId;

        $this->actualizarItem($id, $campos);
        $this->json(null);
    }

    public function delegar(): void
    {
        $this->requireAuth();
        $id = $this->itemId();
        $this->assertItem($id);

        $personaId  = (int) $this->input('persona_id', 0);
        $contextoId = (int) $this->input('contexto_id', 0);

        if ($personaId <= 0)  $this->error('La persona es obligatoria.');
        if ($contextoId <= 0) $this->error('El contexto es obligatorio.');

        $proyectoId  = $this->intONull('proyecto_id');
        $tipoTiempo  = $this->tipoTiempoValido($this->input('tipo_tiempo', 'ninguno'));
        $fechaAccion = $this->fechaONull('fecha_accion');

        $campos = [
            'tipo'             => 'delegada',
            'persona_id'       => $personaId,
            'contexto_id'      => $contextoId,
            'tipo_tiempo'      => $tipoTiempo,
            'fecha_accion'     => $fechaAccion,
            'fecha_delegacion' => date('Y-m-d'),
        ];

        if ($proyectoId !== null) $campos['proyecto_id'] = $proyectoId;

        $this->actualizarItem($id, $campos);
        $this->json(null);
    }

    public function proyecto(): void
    {
        $this->requireAuth();
        $id = $this->itemId();
        $this->assertItem($id);

        $contextoId = (int) $this->input('contexto_id', 0);
        if ($contextoId <= 0) {
            $this->error('El contexto es obligatorio.');
        }

        $proyectoId     = (int) $this->input('proyecto_id', 0);
        $nombreProyecto = trim($this->input('nombre_proyecto', ''));

        if ($proyectoId <= 0 && $nombreProyecto === '') {
            $this->error('Debe indicar un proyecto existente o el nombre de uno nuevo.');
        }

        if ($proyectoId <= 0) {
            $areaId           = $this->intONull('area_id');
            $resultadoDeseado = trim($this->input('resultado_deseado', ''));

            $datos = [
                'usuario_id'       => $this->uid(),
                'nombre'           => $nombreProyecto,
                'resultado_deseado' => $resultadoDeseado !== '' ? $resultadoDeseado : null,
                'area_id'          => $areaId,
            ];

            $cols   = implode(', ', array_keys($datos));
            $places = implode(', ', array_fill(0, count($datos), '?'));
            $db     = Database::connection();
            $db->prepare("INSERT INTO proyectos ({$cols}) VALUES ({$places})")
               ->execute(array_values($datos));
            $proyectoId = (int) $db->lastInsertId();
        }

        $this->actualizarItem($id, [
            'tipo'        => 'proyecto_accion',
            'proyecto_id' => $proyectoId,
            'contexto_id' => $contextoId,
        ]);
        $this->json(null);
    }
}
