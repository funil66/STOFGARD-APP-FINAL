<?php

return [
    'parcelas' => [
        2 => (float) env('STONE_COEF_2X', 1.0459), // Coeficiente 2x (4.59%)
        3 => (float) env('STONE_COEF_3X', 1.0549), // Coeficiente 3x (5.49%)
    ],
    'desconto_pix' => (float) env('STONE_DESCONTO_PIX', 0.10), // 10%
];
