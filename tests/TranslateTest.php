<?php

use CreativeCrafts\LaravelAiAssistant\Tasks\Translate;

it('translate a string to swedish', function (): void {
      $translation = Translate::text('Chair')->toLanguageName('swedish');
      expect($translation)->toContain('Stol');
})->group('translate');
