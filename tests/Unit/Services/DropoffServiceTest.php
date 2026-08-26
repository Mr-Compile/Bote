<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\DropoffStatus;
use App\Enums\Unit;
use App\Models\Dropoff;
use App\Models\RecyclableType;
use App\Repositories\DropoffRepositoryInterface;
use App\Repositories\PointsRepositoryInterface;
use App\Services\DropoffService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeConnection;
use Tests\Support\FakeDropoffRepository;
use Tests\Support\FakePointsRepository;

final class DropoffServiceTest extends TestCase
{
    private FakeConnection $db;
    private FakeDropoffRepository $dropoffs;
    private FakePointsRepository $points;
    private DropoffService $service;

    protected function setUp(): void
    {
        $this->db = new FakeConnection();
        $this->dropoffs = new FakeDropoffRepository();
        $this->points = new FakePointsRepository();
        $this->service = new DropoffService(
            db: $this->db,
            dropoffs: $this->dropoffs,
            points: $this->points,
        );
    }

    public function test_verify_awards_points_and_completes_dropoff(): void
    {
        $dropoff = $this->createPendingDropoff(100);
        $this->dropoffs->save($dropoff);

        $verified = $this->service->verify(
            dropoffId: $dropoff->dropoffId,
            staffId: 5,
            notes: 'Verified at collection point',
        );

        self::assertSame(DropoffStatus::Completed, $verified->status);
        self::assertSame(5, $verified->verifiedBy);

        self::assertSame(100, $this->points->lastAmount);
        self::assertSame($dropoff->residentId, $this->points->lastUserId);
        self::assertSame($dropoff->dropoffId, $this->points->lastDropoffId);
    }

    public function test_reject_does_not_award_points(): void
    {
        $dropoff = $this->createPendingDropoff(100);
        $this->dropoffs->save($dropoff);

        $rejected = $this->service->reject(
            dropoffId: $dropoff->dropoffId,
            staffId: 5,
            reason: 'Wrong material',
        );

        self::assertSame(DropoffStatus::Rejected, $rejected->status);
        self::assertNull($this->points->lastAmount);
    }

    public function test_verify_throws_when_not_pending(): void
    {
        $dropoff = $this->createPendingDropoff(100)->rejected(verifiedBy: 5, reason: 'x');
        $this->dropoffs->save($dropoff);

        $this->expectException(\DomainException::class);
        $this->service->verify(
            dropoffId: $dropoff->dropoffId,
            staffId: 5,
            notes: 'Should fail',
        );
    }

    public function test_reject_throws_when_not_pending(): void
    {
        $dropoff = $this->createPendingDropoff(100)->verified(verifiedBy: 5, notes: 'x');
        $this->dropoffs->save($dropoff);

        $this->expectException(\DomainException::class);
        $this->service->reject(
            dropoffId: $dropoff->dropoffId,
            staffId: 5,
            reason: 'Should fail',
        );
    }

    private function createPendingDropoff(int $pointsEarned): Dropoff
    {
        $type = new RecyclableType(
            typeId: 2,
            name: 'Glass',
            unit: Unit::Pcs,
            pointsPerKg: null,
            pointsPerUnit: 1.0,
            image: null,
        );

        return Dropoff::fromArray([
            'dropoff_id' => 7,
            'resident_id' => 10,
            'point_id' => 2,
            'type_id' => 2,
            'weight_kg' => 5.0,
            'quantity_pcs' => 100,
            'points_earned' => $pointsEarned,
            'status' => 'Pending',
            'verified_by' => null,
            'verification_notes' => null,
            'created_at' => '2025-11-01 09:00:00',
            'verified_at' => null,
        ], $type);
    }
}
