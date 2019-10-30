<?php

return [

    /**
     * This is the amount of GPU cards that we have available for customer use.
     * We will need to update this as we purchase more GPU cards.
     */

    'cards_available' => env('GPU_CARDS_AVAILABLE', 6),

    /**
     * Available card profiles and how much GPU card resources they use
     */

    'card_profiles' => [
        'grid_v100d-16q' => 0.5,
        'grid_v100d-32q' => 1
    ]
];
