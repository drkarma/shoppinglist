<!DOCTYPE html>
<html lang="sv">
<head>
  <meta charset="UTF-8">
  <title>Skapa inköpslista</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h3 mb-4">Skapa en ny inköpslista</h1>
          <form method="POST" action="?action=create" class="row g-3">
            <div class="col-12">
              <label for="title" class="form-label">Listans namn</label>
              <input type="text" id="title" name="title" class="form-control" required placeholder="Ex: Storhandla lördag">
            </div>
            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary">Skapa lista</button>
            </div>
          </form>

          <!-- Disclaimer -->
          <div class="alert alert-warning mt-4" role="alert">
            <h2 class="h6 mb-2">Viktigt att veta</h2>
            <ul class="mb-2">
              <li>Tjänsten är gratis och <strong>experimentell</strong> – inga garantier ges.</li>
              <li>Dina listor lagras i en enkel databas som kan <strong>tömmas vid behov</strong>; räkna inte med att de finns kvar för alltid.</li>
              <li>Vi använder inga spårande cookies och <strong>säljer inte</strong> data till tredje part.</li>
              <li>För att kunna återvända till din lista måste du <strong>kopiera och spara den unika länken</strong>. Vi kan inte tillhandahålla borttappade länkar.</li>
            </ul>
            <p class="mb-0">
              Listan är jättepraktisk – dela länken med vänner för att handla samtidigt eller bocka av över tid.
              Ha det så kul och hoppas den är användbar!
             <p class="mb-2">
             </P>

              Om du går till din lista via en länk i exempelvis messenger så öppnas den inte upp i din vanliga webbläsare. Editeringsfunktionen på befintliga poster (ändra pris, namn, antal) kan då vara begränsad. Kopiera då länken istället och öppna i en webbläsare (typ Safari, Chrome eller Firefox).
          </div>

        </div>
      </div>
    </div>
  </div>
</body>
</html>
