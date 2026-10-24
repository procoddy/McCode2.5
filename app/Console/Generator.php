<?php

declare(strict_types=1);

namespace App\Console;

use App\Support\Str;
use InvalidArgumentException;

/**
 * Scaffolds code from the templates in app/Console/stubs.
 * Used by `php bin/make.php`; edit the stubs to change what gets generated.
 */
final class Generator
{
    /** Field type => column definition, validation rule, form input, PHP type. */
    private const TYPES = [
        'string' => ['sql' => 'VARCHAR(255) NOT NULL', 'rule' => 'required|string|max:255', 'input' => 'text', 'php' => 'string'],
        'text' => ['sql' => 'TEXT NOT NULL', 'rule' => 'required|string|max:65535', 'input' => 'textarea', 'php' => 'string'],
        'int' => ['sql' => 'INT NOT NULL DEFAULT 0', 'rule' => 'required|integer', 'input' => 'number', 'php' => 'int'],
        'bool' => ['sql' => 'TINYINT(1) NOT NULL DEFAULT 0', 'rule' => 'required|boolean', 'input' => 'checkbox', 'php' => 'bool'],
    ];

    private const RESERVED_FIELDS = ['id', 'created_at', 'updated_at', '_token', '_method'];

    /** Variable names the layouts and views already use. */
    private const RESERVED_NAMES = ['title', 'content', 'errors', 'status', 'user', 'app_name', 'values', 'view', 'has_old'];

    private const ROUTE_MARKER = '// make:resource routes';

    private const SIDEBAR_MARKER = '<?php /* make:resource links */ ?>';

    public function __construct(private readonly string $basePath)
    {
    }

    /**
     * @return array{created: list<string>, updated: list<string>, notes: list<string>, url: string}
     */
    public function resource(string $name, ?string $module = null, ?string $fields = null): array
    {
        $class = $this->className($name, 'resource');
        $module = $this->className($module ?? $class, 'module');
        $snake = Str::snake($class);
        $table = Str::plural($snake);

        if (in_array($snake, self::RESERVED_NAMES, true)) {
            throw new InvalidArgumentException("[{$class}] clashes with a variable the views already use. Pick another name.");
        }

        $variable = Str::camel($snake);
        $url = '/' . str_replace('_', '-', $table);
        $title = ucfirst(str_replace('_', ' ', $snake));
        $titlePlural = ucfirst(str_replace('_', ' ', $table));
        $fieldDefinitions = $this->parseFields($fields ?? 'name:string');

        $replacements = [
            '{{namespace}}' => "App\\Modules\\{$module}",
            '{{class}}' => $class,
            '{{variable}}' => $variable,
            '{{variables}}' => Str::camel($table),
            '{{table}}' => $table,
            '{{url}}' => $url,
            '{{viewPath}}' => "pages.{$table}",
            '{{title}}' => $title,
            '{{titleLower}}' => strtolower($title),
            '{{titlePlural}}' => $titlePlural,
            '{{titlePluralLower}}' => strtolower($titlePlural),
            ...$this->fieldChunks($fieldDefinitions, $variable),
        ];

        $files = [
            "app/Modules/{$module}/Models/{$class}.php" => 'model',
            "app/Modules/{$module}/Repositories/{$class}Repository.php" => 'repository',
            "app/Modules/{$module}/Requests/{$class}Request.php" => 'request',
            "app/Modules/{$module}/Controllers/{$class}Controller.php" => 'controller',
            "resources/views/pages/{$table}/index.php" => 'views/index',
            "resources/views/pages/{$table}/create.php" => 'views/create',
            "resources/views/pages/{$table}/edit.php" => 'views/edit',
            "resources/views/pages/{$table}/show.php" => 'views/show',
            "resources/views/pages/{$table}/_form.php" => 'views/_form',
            $this->migrationPath("create_{$table}_table") => 'migration',
        ];

        if (glob($this->path("database/migrations/*_create_{$table}_table.php")) !== []) {
            throw new InvalidArgumentException("A migration that creates the [{$table}] table already exists.");
        }

        $this->writeAll($files, $replacements);

        $updated = [];
        $notes = [];
        $routeLine = "\$router->resource('{$url}', \\App\\Modules\\{$module}\\Controllers\\{$class}Controller::class);";

        if ($this->insertBeforeMarker('routes/web.php', self::ROUTE_MARKER, $routeLine)) {
            $updated[] = 'routes/web.php';
        } else {
            $notes[] = "Add this route to routes/web.php inside the 'auth' group:\n    {$routeLine}";
        }

        if ($this->insertBeforeMarker('resources/views/partials/sidebar.php', self::SIDEBAR_MARKER, "<a href=\"{$url}\">{$titlePlural}</a>")) {
            $updated[] = 'resources/views/partials/sidebar.php';
        }

        return ['created' => array_keys($files), 'updated' => $updated, 'notes' => $notes, 'url' => $url];
    }

    /** @return string The created file, relative to the project root. */
    public function migration(string $name): string
    {
        $name = Str::snake($name);

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid migration name [{$name}]. Use snake_case, e.g. add_bio_to_users_table.");
        }

        $path = $this->migrationPath($name);
        $this->writeAll([$path => 'migration-blank'], []);

        return $path;
    }

    private function className(string $name, string $label): string
    {
        $class = Str::studly(trim($name));

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $class)) {
            throw new InvalidArgumentException("Invalid {$label} name [{$name}]. Use letters and numbers, e.g. Item or BankAccount.");
        }

        return $class;
    }

    /**
     * Parse "name:string,price:int,description:text,is_active:bool".
     *
     * @return array<string, string> field => type
     */
    private function parseFields(string $definition): array
    {
        $fields = [];

        foreach (array_filter(array_map('trim', explode(',', $definition))) as $part) {
            [$field, $type] = array_pad(array_map('trim', explode(':', $part, 2)), 2, 'string');
            $field = Str::snake($field);

            if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $field) || in_array($field, self::RESERVED_FIELDS, true)) {
                throw new InvalidArgumentException("Invalid field name [{$field}].");
            }

            if (!isset(self::TYPES[$type])) {
                throw new InvalidArgumentException(
                    "Unknown type [{$type}] for field [{$field}]. Available: " . implode(', ', array_keys(self::TYPES)) . '.',
                );
            }

            if (isset($fields[$field])) {
                throw new InvalidArgumentException("Field [{$field}] is listed twice.");
            }

            $fields[$field] = $type;
        }

        if ($fields === []) {
            throw new InvalidArgumentException('At least one field is required, e.g. --fields="name:string".');
        }

        return $fields;
    }

    /**
     * Build the field-dependent parts of each stub.
     *
     * @param array<string, string> $fields
     * @return array<string, string>
     */
    private function fieldChunks(array $fields, string $variable): array
    {
        $chunks = array_fill_keys([
            '{{modelProperties}}', '{{modelFromRow}}', '{{modelToArray}}', '{{rules}}', '{{prepare}}',
            '{{columns}}', '{{formFields}}', '{{tableHeaders}}', '{{tableCells}}', '{{showRows}}',
        ], '');
        $fieldList = [];

        foreach ($fields as $field => $type) {
            $property = Str::camel($field);
            $label = ucfirst(str_replace('_', ' ', $field));
            $spec = self::TYPES[$type];
            $fieldList[] = "'{$field}'";

            $chunks['{{modelProperties}}'] .= "        public readonly {$spec['php']} \${$property},\n";
            $chunks['{{modelFromRow}}'] .= "            {$property}: ({$spec['php']}) \$row['{$field}'],\n";
            $chunks['{{modelToArray}}'] .= "            '{$field}' => \$this->{$property},\n";
            $chunks['{{rules}}'] .= "            '{$field}' => '{$spec['rule']}',\n";
            $chunks['{{columns}}'] .= "            `{$field}` {$spec['sql']},\n";

            $chunks['{{prepare}}'] .= match ($type) {
                'bool' => "        \$data['{$field}'] = empty(\$data['{$field}']) ? '0' : '1';\n\n",
                'int' => '',
                default => "        if (is_string(\$data['{$field}'] ?? null)) {\n"
                    . "            \$data['{$field}'] = trim(\$data['{$field}']);\n        }\n\n",
            };

            $valueLine = $type === 'bool'
                ? "    'checked' => \$hasOld ? (bool) old('{$field}', false) : !empty(\$values['{$field}']),\n"
                : "    'value' => old('{$field}', \$values['{$field}'] ?? ''),\n";
            $chunks['{{formFields}}'] .= "<?= \$this->component('form-field', [\n"
                . "    'name' => '{$field}',\n"
                . "    'label' => '{$label}',\n"
                . "    'type' => '{$spec['input']}',\n"
                . $valueLine
                . "    'errors' => \$errors['{$field}'] ?? [],\n"
                . "]) ?>\n\n";

            $display = match ($type) {
                'bool' => "<?= \${$variable}->{$property} ? 'Yes' : 'No' ?>",
                default => "<?= e(\${$variable}->{$property}) ?>",
            };

            // Long text would make the table unreadable; it is shown on the detail page only.
            if ($type !== 'text') {
                $chunks['{{tableHeaders}}'] .= "                    <th>{$label}</th>\n";
                $chunks['{{tableCells}}'] .= "                    <td>{$display}</td>\n";
            }

            $chunks['{{showRows}}'] .= "    <dt>{$label}</dt>\n    <dd>{$display}</dd>\n";
        }

        $chunks['{{fieldList}}'] = implode(', ', $fieldList);
        $chunks['{{formFields}}'] = rtrim($chunks['{{formFields}}']);

        return $chunks;
    }

    /**
     * Write every file, or none if any already exists.
     *
     * @param array<string, string> $files relative path => stub name
     */
    private function writeAll(array $files, array $replacements): void
    {
        $existing = array_filter(array_keys($files), fn (string $file) => file_exists($this->path($file)));

        if ($existing !== []) {
            throw new InvalidArgumentException("Nothing was created because these files already exist:\n  " . implode("\n  ", $existing));
        }

        foreach ($files as $file => $stub) {
            $target = $this->path($file);

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0775, true);
            }

            file_put_contents($target, strtr((string) file_get_contents(__DIR__ . "/stubs/{$stub}.stub"), $replacements));
        }
    }

    /** Insert $line above the marker using the marker's indentation. */
    private function insertBeforeMarker(string $file, string $marker, string $line): bool
    {
        $path = $this->path($file);
        $contents = is_file($path) ? (string) file_get_contents($path) : '';

        if (!preg_match('/^([ \t]*)' . preg_quote($marker, '/') . '/m', $contents, $match)) {
            return false;
        }

        if (str_contains($contents, $line)) {
            return true;
        }

        $indent = $match[1];
        file_put_contents($path, str_replace($indent . $marker, $indent . $line . "\n" . $indent . $marker, $contents));

        return true;
    }

    private function migrationPath(string $name): string
    {
        return 'database/migrations/' . date('Y_m_d_His') . "_{$name}.php";
    }

    private function path(string $relative): string
    {
        return $this->basePath . '/' . $relative;
    }
}
