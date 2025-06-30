<?php
if (!function_exists('app')) {
    function app($service = null) {
        // Retorna um stub de ORM para os testes
        if ($service === 'cycle.orm') {
            return new class {
                public function getSchema() {
                    return new class {
                        public function getRoles() { return ['User', 'Post']; }
                    };
                }
            };
        }
        if ($service === 'cycle.migrator') {
            return new class {
                public function run() { return true; }
            };
        }
        return null;
    }
}
