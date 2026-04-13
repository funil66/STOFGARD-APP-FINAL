<?php
$file = 'resources/views/pdf/orcamento.blade.php';
$content = file_get_contents($file);

$search1 = <<<HTML
        <tbody>
            <tr>
                <td>
    <div style="width: 100%;">
        @foreach(\$mainBlocks as \$block)
HTML;

$replace1 = <<<HTML
        <tbody>
        @foreach(\$mainBlocks as \$block)
            <tr>
                <td style="padding: 10px 0;">
    <div style="width: 100%;">
HTML;

$search2 = <<<HTML
        @endforeach
        
        <!-- DIGITAL_SEAL_SLOT -->
    </div>
                </td>
            </tr>
        </tbody>
HTML;

$replace2 = <<<HTML
    </div>
                </td>
            </tr>
        @endforeach
        
            <tr>
                <td style="padding-top: 20px;">
                    <!-- DIGITAL_SEAL_SLOT -->
                </td>
            </tr>
        </tbody>
HTML;

$content = str_replace($search1, $replace1, $content);
$content = str_replace($search2, $replace2, $content);
file_put_contents($file, $content);
echo "Patched.";
