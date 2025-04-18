# 🔍 Smart Hybrid Search in Laravel 12

```php
// Controller: app/Http/Controllers/SearchController.php

/**
 * Search for products by name or description using a hybrid strategy:
 * - First, perform a Full-Text search for speed and precision.
 * - If not enough results, fallback to typo correction using Levenshtein distance.
 */
public function search(Request $request)
{
    // Get the raw search input from the user
    $q = $request->input('q');

    // Define the max number of results to return
    $limit = 10;

    // Normalize the search input (trim and lowercase)
    $normalized = strtolower(trim($q));

    /**
     * STEP 1: Full-Text Search (Fast & Accurate)
     * Uses MySQL's MATCH ... AGAINST with BOOLEAN MODE for efficient indexed search.
     */
    $results = Product::whereRaw(
        "MATCH(name, description) AGAINST (? IN BOOLEAN MODE)",
        [$normalized]
    )->limit($limit)->get();

    // If we already have enough results, return them directly
    if ($results->count() >= $limit) {
        return response()->json($results);
    }

    /**
     * STEP 2: Fallback using Levenshtein Distance (Typo Correction)
     * We fetch a sample of products, split their names into words,
     * then compare each word against the user input using levenshtein()
     * to detect close matches (with typos).
     */
    $fallback = Product::select('id', 'name', 'description')
        ->limit(100) // Limit the number of records to scan (performance)
        ->get()
        ->filter(function ($product) use ($normalized) {
            // Split the product name into words (e.g. "عود مسك فاخر" → ["عود", "مسك", "فاخر"])
            $nameWords = explode(' ', strtolower($product->name));

            foreach ($nameWords as $word) {
                // Skip very short/common words (e.g. "في", "من", etc.)
                if (mb_strlen($word) < 3) continue;

                // Calculate the Levenshtein distance between the input and each word
                $distance = levenshtein($normalized, $word);

                // Allow a match if the distance is within half the input length
                if ($distance <= mb_strlen($normalized) / 2) {
                    return true; // Close match found
                }
            }

            return false; // No match found
        })
        ->take($limit - $results->count()); // Only take what's missing to reach $limit

    /**
     * STEP 3: Combine the results
     * Merge the Full-Text and Levenshtein results, remove any duplicates,
     * and return up to the defined limit.
     */
    return response()->json(
        $results->merge($fallback)->unique('id')->take($limit)->values()
    );
}

```

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Smart Laravel Search</title>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  
  <style>
    /* Layout and font setup */
    body {
      font-family: sans-serif;
      padding: 20px;
      max-width: 600px;
      margin: auto;
      direction: rtl; /* RTL direction for Arabic text */
    }

    /* Input box styling */
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    /* Search result item style */
    li {
      padding: 8px;
      background: #f9f9f9;
      border-bottom: 1px solid #eee;
      list-style: none;
    }
  </style>
</head>
<body>
  <h1>ابحث عن منتج</h1>
  
  <!-- Search input field -->
  <input type="text" id="searchInput" placeholder="اكتب اسم المنتج...">

  <!-- Dynamic search results list -->
  <ul id="searchResults"></ul>

  <script>
    // DOM elements
    const input = document.getElementById('searchInput');
    const list = document.getElementById('searchResults');
    let timer;

    input.addEventListener('input', () => {
      clearTimeout(timer); // Clear previous timeout (for debounce)

      const query = input.value.trim();

      // Skip search if input is too short
      if (query.length < 2) {
        list.innerHTML = '';
        return;
      }

      // Debounce: wait 300ms after user stops typing before firing request
      timer = setTimeout(() => {
        axios.get(`/search?q=${encodeURIComponent(query)}`)
          .then(res => {
            list.innerHTML = ''; // Clear previous results

            // Render each product result as a <li> element
            res.data.forEach(item => {
              const li = document.createElement('li');
              li.innerHTML = `<strong>${item.name}</strong><br>${item.description ?? ''}`;
              list.appendChild(li);
            });
          });
      }, 300);
    });
  </script>
</body>
</html>
```
