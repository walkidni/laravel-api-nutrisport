{{ $reportTitle }}

Date
Date : {{ $formattedReportDate }}

Produit le plus vendu
Produit le plus vendu : {{ $mostSoldProductName }}
Unites vendues : {{ $mostSoldProductValue }}

Produit le moins vendu
Produit le moins vendu : {{ $leastSoldProductName }}
Unites vendues : {{ $leastSoldProductValue }}

Produit au CA maximum
Produit au CA maximum : {{ $highestTurnoverProductName }}
CA : {{ $highestTurnoverProductAmount }}

Produit au CA minimum
Produit au CA minimum : {{ $lowestTurnoverProductName }}
CA : {{ $lowestTurnoverProductAmount }}

CA par site
@foreach ($siteTurnovers as $siteTurnover)
{{ $siteTurnover['site_code'] }} : {{ $siteTurnover['turnover_amount'] }}
@endforeach
