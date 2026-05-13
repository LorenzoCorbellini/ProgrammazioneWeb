<?php
// filter.php
// Includere DOPO aver definito $filtro_config nella pagina chiamante.
//
// Struttura di $filtro_config:
// $filtro_config = [
//     'action' => 'bacheche.php',   // opzionale, default: pagina corrente
//     'campi'  => [
//         ['tipo' => 'text',     'name' => 'titolo',    'label' => 'Titolo'],
//         ['tipo' => 'date',     'name' => 'data',      'label' => 'Data'],
//         ['tipo' => 'select',   'name' => 'stato',     'label' => 'Stato',
//          'opzioni' => ['Attivo', 'Archiviato']],
//         ['tipo' => 'checkbox', 'name' => 'solo_pub',  'label' => 'Solo pubblici'],
//     ]
// ];

if (empty($filtro_config['campi'])) return;

$action = htmlspecialchars($filtro_config['action'] ?? $_SERVER['PHP_SELF']);
?>

<div id="filtro">
    <h3>Filtri</h3>
    <form method="GET" action="<?= $action ?>">
        <?php foreach ($filtro_config['campi'] as $campo):
            $name  = htmlspecialchars($campo['name']);
            $label = htmlspecialchars($campo['label']);
            $value = htmlspecialchars($_GET[$campo['name']] ?? '');
        ?>

            <?php if ($campo['tipo'] === 'checkbox'): ?>

                <label class="checkbox-label">
                    <input type="checkbox"
                           name="<?= $name ?>"
                           value="1"
                           <?= isset($_GET[$campo['name']]) ? 'checked' : '' ?>>
                    <?= $label ?>
                </label>

            <?php elseif ($campo['tipo'] === 'select'): ?>

                <label for="<?= $name ?>"><?= $label ?></label>
                <select name="<?= $name ?>" id="<?= $name ?>">
                    <option value="">Tutti</option>
                    <?php foreach ($campo['opzioni'] as $opzione):
                        $opt = htmlspecialchars($opzione);
                    ?>
                        <option value="<?= $opt ?>"
                            <?= $value === $opt ? 'selected' : '' ?>>
                            <?= $opt ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php else: ?>

                <label for="<?= $name ?>"><?= $label ?></label>
                <input type="<?= htmlspecialchars($campo['tipo']) ?>"
                       name="<?= $name ?>"
                       id="<?= $name ?>"
                       value="<?= $value ?>"
                       placeholder="<?= $label ?>">

            <?php endif; ?>

        <?php endforeach; ?>

        <button type="submit">Applica</button>
        <button type="button" class="reset"
                onclick="window.location='<?= $action ?>'">
            Reimposta
        </button>
    </form>
</div>