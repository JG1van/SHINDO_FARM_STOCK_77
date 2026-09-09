<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Nota Shindo Farm 77</title>
<style>
  @page {
    size: 58mm auto;
    margin: 0;
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  html, body {
    width: 48mm;
    overflow-wrap: break-word;
  }

  body {
    font-family: 'Courier New', monospace;
    font-size: 10.5px;
    line-height: 1.35;
    color: #000;
    padding: 0.5mm 0;
    font-weight: bold;
  }

  .center {
    text-align: center;
  }

  .farm-name {
    font-size: 13px;
    letter-spacing: 0.5px;
    margin-bottom: 0.3mm;
  }

  .tagline {
    font-size: 9.5px;
    margin-bottom: 0.5mm;
  }

  .contact {
    font-size: 8.5px;
    line-height: 1.35;
    margin-bottom: 1.2mm;
  }

  .divider {
    border-top: 1.5px dashed #000;
    margin: 1mm 0;
  }

  .divider-solid {
    border-top: 2px solid #000;
    margin: 1mm 0;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }

  .info-table td {
    font-size: 9.5px;
    padding: 0.3mm 0;
    vertical-align: top;
  }

  .info-table .label {
    width: 38%;
    padding-right: 1mm;
  }

  .info-table .value {
    width: 62%;
    text-align: right;
    word-break: break-all;
    overflow-wrap: anywhere;
  }

  .item-name {
    font-size: 11px;
    padding-top: 0.5mm;
    padding-bottom: 0.3mm;
  }

  .item-detail td {
    font-size: 9.5px;
    padding-bottom: 0.6mm;
  }

  .item-detail .qty {
    text-align: left;
    padding-right: 1mm;
  }

  .item-detail .price {
    text-align: right;
    word-break: break-all;
    overflow-wrap: anywhere;
  }

  .total-table td {
    font-size: 9.5px;
    padding: 0.3mm 0;
  }

  .total-table .label {
    padding-right: 1mm;
  }

  .total-table .value {
    text-align: right;
    word-break: break-all;
    overflow-wrap: anywhere;
  }

  .grand-total td {
    font-size: 12px;
    padding-top: 0.5mm;
  }

  .grand-total .label {
    padding-right: 1mm;
  }

  .grand-total .value {
    text-align: right;
    word-break: break-all;
    overflow-wrap: anywhere;
  }

  .footer {
    text-align: center;
    font-size: 8.5px;
    margin-top: 1.2mm;
    line-height: 1.35;
  }

  .footer-thanks {
    font-size: 10px;
    margin-bottom: 0.5mm;
  }
</style>
</head>
<body>

  <div class="center">
    <div class="farm-name">SHINDO FARM 77</div>
    <div class="tagline">Telur Ayam Kampung Segar</div>
    <div class="contact">
      Jl. Sonopakis Kidul, Ngestiharjo<br>
      Kasihan, Bantul, DIY<br>
      WA 0878-3921-0796
    </div>
  </div>

  <div class="divider-solid"></div>

  <table class="info-table">
    <tr>
      <td class="label">Tanggal</td>
      <td class="value">{{ \Carbon\Carbon::parse($penjualan->tanggal)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <td class="label">Pembeli</td>
      <td class="value">{{ $penjualan->nama_pembeli }}</td>
    </tr>
  </table>

  <div class="divider"></div>

  <table>
    <tr>
      <td class="item-name" colspan="2">Telur Ayam Kampung</td>
    </tr>
    <tr class="item-detail">
      <td class="qty">{{ $penjualan->jumlah_telur }} butir</td>
      <td class="price">Rp {{ number_format($penjualan->total_harga, 0, ',', '.') }}</td>
    </tr>
    @if($penjualan->bonus > 0)
    <tr>
      <td class="item-name" colspan="2">Bonus</td>
    </tr>
    <tr class="item-detail">
      <td class="qty">{{ $penjualan->bonus }} butir</td>
      <td class="price">-</td>
    </tr>
    @endif
  </table>

  <div class="divider"></div>

  <table class="total-table">
    <tr>
      <td class="label">Subtotal</td>
      <td class="value">Rp {{ number_format($penjualan->total_harga, 0, ',', '.') }}</td>
    </tr>
  </table>

  <div class="divider"></div>

  <table>
    <tr class="grand-total">
      <td class="label">TOTAL</td>
      <td class="value">Rp {{ number_format($penjualan->total_harga, 0, ',', '.') }}</td>
    </tr>
  </table>

  <div class="divider"></div>

  <div class="footer">
    <div class="footer-thanks">Terima Kasih!</div>
    <div>Barang yang sudah dibeli<br>tidak dapat dikembalikan</div>
    <div style="margin-top:1.2mm;">shindo-farm-77.my.id</div>
  </div>

  <script>
    window.onload = function() {
      window.print();
    };
  </script>

</body>
</html>