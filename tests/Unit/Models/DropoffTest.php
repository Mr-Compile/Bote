<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\DropoffStatus;
use App\Enums\Unit;
use App\Models\Dropoff;
use App\Models\RecyclableType;
use PHPUnit\Framework\TestCase;

final class DropoffTest extends TestCase
{
    public function test_from_array_creates_dropoff(): void
    {
        $row = [
            'dropoff_id' => 7,
            'resident_id' => 10,
            'point_id' => 2,
            'type_id' => 2,
            'weight_kg' => 5.0,
            'quantity_pcs' => 100,
            'points_earned' => 100,
            'status' => 'Pending',
            'verified_by' => null,
            'verification_notes' => null,
            'created_at' => '2025-11-01 09:00:00',
            'verified_at' => null,
        ];

        $type = new RecyclableType(
            typeId: 2,
            name: 'Glass',
            unit: Unit::Pcs,
            pointsPerKg: null,
            pointsPerUnit: 1.0,
            image: null,
        );

        $dropoff = Dropoff::fromArray($row, $type);

        self::assertSame(7, $dropoff->dropoffId);
        self::assertSame(DropoffStatus::Pending, $dropoff->status);
        self::assertSame(100, $dropoff->pointsEarned);
    }

    public function test_verified_returns_new_dropoff(): void
    {
        $dropoff = $this->pendingDropoff();

        $verified = $dropoff->verified(verifiedBy: 5, notes: 'Good condition');

        self::assertSame(DropoffStatus::Completed, $verified->status);
        self::assertSame(5, $verified->verifiedBy);
        self::assertSame('Good condition', $verified->verificationNotes);
    }

    public function test_rejected_returns_new_dropoff(): void
    {
        $dropoff = $this->pendingDropoff();

        $rejected = $dropoff->rejected(verifiedBy: 5, reason: 'Wrong material');

        self::assertSame(DropoffStatus::Rejected, $rejected->status);
        self::assertSame('Wrong material', $rejected->verificationNotes);
    }

    private function pendingDropoff(): Dropoff
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
            'points_earned' => 100,
            'status' => 'Pending',
            'verified_by' => null,
            'verification_notes' => null,
            'created_at' => '2025-11-01 09:00:00',
            'verified_at' => null,
        ], $type);
    }
}
