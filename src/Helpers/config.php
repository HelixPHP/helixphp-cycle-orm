<?php
if (!function_exists('config')) {
  /**
   * Helper para acessar configurações da aplicação
   *
   * @param string|null $key
   * @param mixed|null $default
   * @return mixed
   * @psalm-suppress MixedMethodCall
   * @phpstan-ignore-next-line
   */
  function config(?string $key = null, $default = null): mixed
  {
    // Tenta buscar do container global, se existir
    if (function_exists('app') && app()) {
      try {
        /** @var Express\Core\Application $container */
        $container = app();
        $configService = null;
        if (method_exists($container, 'has') && $container->has('config') && method_exists($container, 'get')) {
          $configService = $container->get('config', []);
        } elseif (method_exists($container, 'make')) {
          $configService = $container->make('config');
        }
        if ($configService && method_exists($configService, 'get')) {
          return $configService->get($key, $default);
        }
      } catch (\Exception $e) {
        // Se houver qualquer erro, retorna o default silenciosamente
        return $default;
      }
    }
    // Fallback: retorna default
    return $default;
  }
}
