<?php

if (!function_exists('helpersTestApp')) {
    function helpersTestApp(?string $service = null): mixed
    {
        // Retorna um stub de ORM para os testes
        if ('cycle.orm' === $service) {
            return new class () {
                public function getSchema(): object
                {
                    return new class () {
                        /** @return array<int, string> */
                        public function getRoles(): array
                        {
                            return ['User', 'Post'];
                        }
                    };
                }
            };
        }
        if ('cycle.migrator' === $service) {
            return new class () {
                public function run(): bool
                {
                    return true;
                }
            };
        }

        return null;
    }
}
