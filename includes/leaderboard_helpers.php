<?php

/**
 * @param array<string, mixed> $user
 * @return array<string, mixed>
 */
function getProfileDisplay(array $user): array
{
    if (!empty($user['profile_photo'])) {
        $thumb_path = '../uploads/profiles/thumbs/' . $user['profile_photo'];
        $regular_path = '../uploads/profiles/' . $user['profile_photo'];

        if (file_exists($thumb_path)) {
            return [
                'type' => 'photo',
                'src' => $thumb_path,
            ];
        }

        if (file_exists($regular_path)) {
            return [
                'type' => 'photo',
                'src' => $regular_path,
            ];
        }
    }

    $first_initial = !empty($user['first_name']) ? strtoupper(substr((string) $user['first_name'], 0, 1)) : 'U';
    $last_initial = !empty($user['last_name']) ? strtoupper(substr((string) $user['last_name'], 0, 1)) : 'S';

    return [
        'type' => 'initials',
        'initials' => $first_initial . $last_initial,
    ];
}

/**
 * @param int|float|string $points
 * @return array<string, string>
 */
function getBadge($points): array
{
    $points = (int) $points;

    return match (true) {
        $points >= 3000 => ['name' => '🏆 Eco Champion', 'class' => 'bg-yellow-100 text-yellow-800'],
        $points >= 2000 => ['name' => '🥈 Silver Recycler', 'class' => 'bg-bote-stone-100 text-bote-stone-700'],
        $points >= 1000 => ['name' => '🥉 Bronze Warrior', 'class' => 'bg-orange-100 text-orange-700'],
        $points >= 500 => ['name' => '🛡️ Eco Warrior', 'class' => 'bg-bote-green-100 text-bote-green-700'],
        $points >= 250 => ['name' => '🌿 Green Guardian', 'class' => 'bg-bote-info-100 text-bote-info-700'],
        $points >= 100 => ['name' => '🌻 Nature Friend', 'class' => 'bg-bote-green-100 text-bote-green-700'],
        $points >= 50 => ['name' => '🌱 Eco Starter', 'class' => 'bg-bote-green-100 text-bote-green-700'],
        $points >= 10 => ['name' => '🌱 Recycling Beginner', 'class' => 'bg-bote-green-100 text-bote-green-700'],
        default => ['name' => '🌍 Earth Helper', 'class' => 'bg-bote-stone-100 text-bote-stone-600'],
    };
}

/**
 * @param int|float|string $points
 * @return array<string, string>
 */
function getPurokBadge($points): array
{
    $points = (int) $points;

    return match (true) {
        $points >= 10000 => ['name' => '🏆 Eco Champion Purok', 'class' => 'bg-yellow-100 text-yellow-800'],
        $points >= 7000 => ['name' => '🥈 Silver Community', 'class' => 'bg-bote-stone-100 text-bote-stone-700'],
        $points >= 5000 => ['name' => '🥉 Bronze Sustainable', 'class' => 'bg-orange-100 text-orange-700'],
        $points >= 3000 => ['name' => '🌿 Green Community', 'class' => 'bg-bote-green-100 text-bote-green-700'],
        $points >= 1000 => ['name' => '♻️ Recycling Hub', 'class' => 'bg-bote-info-100 text-bote-info-700'],
        $points >= 500 => ['name' => '🌱 Eco Starter', 'class' => 'bg-bote-green-100 text-bote-green-700'],
        $points >= 100 => ['name' => '🌱 Growing Community', 'class' => 'bg-bote-green-100 text-bote-green-700'],
        default => ['name' => '🏘️ New Community', 'class' => 'bg-bote-stone-100 text-bote-stone-600'],
    };
}
