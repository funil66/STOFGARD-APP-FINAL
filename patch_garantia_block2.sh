#!/bin/bash
sed -i '/<div class="valores-section"/,/@endif/d' resources/views/pdf/certificado_garantia.blade.php
