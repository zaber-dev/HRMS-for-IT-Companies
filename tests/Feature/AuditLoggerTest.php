<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('log() creates exactly one AuditLog record with correct fields', function () {
    $actor = User::factory()->create();
    $auditable = User::factory()->create();

    $oldValues = ['name' => 'Old Name'];
    $newValues = ['name' => 'New Name'];

    $logger = new AuditLogger;
    $logger->log($actor, 'user.updated', $auditable, $oldValues, $newValues);

    expect(AuditLog::count())->toBe(1);

    $log = AuditLog::first();

    expect($log->user_id)->toBe($actor->id)
        ->and($log->action)->toBe('user.updated')
        ->and($log->auditable_type)->toBe($auditable->getMorphClass())
        ->and($log->auditable_id)->toBe($auditable->getKey())
        ->and($log->old_values)->toBe($oldValues)
        ->and($log->new_values)->toBe($newValues);
});

test('log() returns the created AuditLog instance', function () {
    $actor = User::factory()->create();
    $auditable = User::factory()->create();

    $logger = new AuditLogger;
    $result = $logger->log($actor, 'user.created', $auditable, null, ['name' => 'Test']);

    expect($result)->toBeInstanceOf(AuditLog::class)
        ->and($result->exists)->toBeTrue();
});

test('log() stores null old_values and new_values when not provided', function () {
    $actor = User::factory()->create();
    $auditable = User::factory()->create();

    $logger = new AuditLogger;
    $logger->log($actor, 'user.deactivated', $auditable);

    $log = AuditLog::first();

    expect($log->old_values)->toBeNull()
        ->and($log->new_values)->toBeNull();
});

test('log() stores null old_values and new_values when explicitly passed as null', function () {
    $actor = User::factory()->create();
    $auditable = User::factory()->create();

    $logger = new AuditLogger;
    $logger->log($actor, 'user.created', $auditable, null, null);

    expect(AuditLog::count())->toBe(1);

    $log = AuditLog::first();

    expect($log->old_values)->toBeNull()
        ->and($log->new_values)->toBeNull();
});

test('log() creates only one record per call', function () {
    $actor = User::factory()->create();
    $auditable = User::factory()->create();

    $logger = new AuditLogger;
    $logger->log($actor, 'user.created', $auditable, null, ['name' => 'Test']);

    expect(AuditLog::count())->toBe(1);
});

test('log() correctly stores the auditable morph class and primary key', function () {
    $actor = User::factory()->create();
    $auditable = User::factory()->create();

    $logger = new AuditLogger;
    $logger->log($actor, 'user.updated', $auditable);

    $log = AuditLog::first();

    expect($log->auditable_type)->toBe('App\\Models\\User')
        ->and($log->auditable_id)->toBe($auditable->id);
});
