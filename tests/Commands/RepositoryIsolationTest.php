<?php

declare(strict_types=1);

it('verifies dedicated services have isolated repositories', function () {
    $statamicService = new StatamicContext\StatamicContext\Services\StatamicDocumentationService;
    $peakService = new StatamicContext\StatamicContext\Services\PeakDocumentationService;

    // Verify they have different repository instances
    $statamicRepo = $statamicService->getRepository();
    $peakRepo = $peakService->getRepository();

    expect($statamicRepo)->not->toBe($peakRepo);
});

it('verifies services use different index paths', function () {
    $statamicService = new StatamicContext\StatamicContext\Services\StatamicDocumentationService;
    $peakService = new StatamicContext\StatamicContext\Services\PeakDocumentationService;

    // Get the repositories and their index paths
    $statamicRepo = $statamicService->getRepository();
    $peakRepo = $peakService->getRepository();

    // Use reflection to access private indexPath property
    $statamicReflection = new ReflectionClass($statamicRepo);
    $peakReflection = new ReflectionClass($peakRepo);

    $statamicIndexProperty = $statamicReflection->getProperty('indexPath');
    $peakIndexProperty = $peakReflection->getProperty('indexPath');

    $statamicIndexPath = $statamicIndexProperty->getValue($statamicRepo);
    $peakIndexPath = $peakIndexProperty->getValue($peakRepo);

    expect($statamicIndexPath)->not->toBe($peakIndexPath);
    expect($statamicIndexPath)->toContain('statamic-docs');
    expect($peakIndexPath)->toContain('statamic-peak-docs');
});

it('verifies different index paths are configured', function () {
    // Check that the config defines separate index files
    $docsIndexPath = config('statamic-context-cli.docs.index_file');
    $peakIndexPath = config('statamic-context-cli.peak_docs.index_file');

    expect($docsIndexPath)->not->toBe($peakIndexPath);
    expect($docsIndexPath)->toContain('statamic-docs');
    expect($peakIndexPath)->toContain('statamic-peak-docs');
});
