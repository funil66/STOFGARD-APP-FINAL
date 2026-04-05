#!/bin/bash
awk '
BEGIN { in_block = 0; }
/@if\(\$block\[.type.\] === .container_duplo.\)/ { in_block = 1; print; next; }
/@endif/ && in_block { 
    print "                {{-- TERMOS DA GARANTIA (SUBSTITUI TOTAIS E PIX) --}}";
    print "                <div style=\"margin-top: 20px; page-break-inside: avoid;\">";
    print "                    <div style=\"text-align: center; margin-bottom: 15px;\">";
    print "                        <div style=\"display: inline-block; padding: 10px 20px; background-color: #fef3c7; color: #d97706; border: 2px solid #f59e0b; border-radius: 5px; font-weight: bold; font-size: 14px; letter-spacing: 1px;\">";
    print "                            GARANTIA DE {{ $garantia->dias_garantia ?? 0 }} DIAS";
    print "                        </div>";
    print "                    </div>";
    print "                    <div class=\"section-header\">TERMOS DA GARANTIA</div>";
    print "                    <div style=\"background: #f9fafb; padding: 15px; border-radius: 8px; font-size: 10px; color: #374151; line-height: 1.6; border: 1px solid #e5e7eb;\">";
    print "                        @if($garantia->observacoes)";
    print "                            {!! nl2br(e($garantia->observacoes)) !!}";
    print "                        @else";
    print "                            <p>Estão garantidos os serviços realizados conforme as especificações técnicas, desde que observadas as condições adequadas de uso e manutenção.</p>";
    print "                            <p>A garantia não cobre defeitos ocasionados por mau uso, agentes externos, produtos químicos não recomendados, ou problemas decorrentes do próprio desgaste natural dos materiais.</p>";
    print "                        @endif";
    print "                    </div>";
    print "                </div>";
    print "            @endif";
    in_block = 0; 
    next; 
}
in_block { next; }
{ print }
' resources/views/pdf/certificado_garantia.blade.php > temp.blade.php
mv temp.blade.php resources/views/pdf/certificado_garantia.blade.php
