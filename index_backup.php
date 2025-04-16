<!DOCTYPE html>
<html>
<head>
    <title>List Search</title>
    <style>
        body {
            background-color: #f0f8ff;
            font-family: Arial, sans-serif;
        }
        .container { 
            max-width: 600px; 
            margin: 20px auto; 
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .search-box { 
            width: 80%; 
            padding: 12px;
            margin-bottom: 2px;
            border: 2px solid #87CEEB;
            border-radius: 5px;
            font-size: 16px;
            margin: 0 auto;
            display: block;
        }
        .dropdown-results { 
            border: 1px solid #87CEEB;
            position: absolute;
            background: white;
            width: calc(100% - 2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
            border-radius: 0 0 5px 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .main-results {
            border: 1px solid #87CEEB;
            background: white;
            width: 100%;
            margin-top: 10px;
            display: none;
            border-radius: 5px;
            column-count: 2;
            column-gap: 20px;
        }
        .list-item[style*="font-weight: bold"] {
            column-span: all;
            background: #87CEEB;
            color: white;
            margin-bottom: 10px;
            text-align: center;
            font-size: 18px;
        }
        .list-item { 
            padding: 12px; 
            cursor: pointer;
            border-bottom: 1px solid #e6f3ff;
            transition: background-color 0.2s;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .list-item:hover {
            background: #e6f3ff;
            color: #1e90ff;
        }
        .search-container {
            position: relative;
        }
        .list-item[data-type="list"] {
            color: #1e90ff;
            font-weight: 500;
        }

        .highlight {
            color:#33caff;
            padding: 0px;
            border-radius: 0px;
        }

        .list-item span.parenthetical {
            font-size: 0.85em;
            color: #666;
        }
        .list-item span.parenthetical::before {
            content: " [";
            padding-right: 4px;
        }
        .list-item span.parenthetical::after {
            content: "]";
            padding-left: 4px;
        }


    </style>
</head>
<body>
    <h1 style="text-align: center; color: #1e90ff; margin-bottom: 20px;">Country Features Search</h1>
    <div class="container">
        <div class="search-container">
            <div style="margin-bottom: 10px;">
                <input type="text" id="searchInput" class="search-box" placeholder="Search by country or features ...">
            </div>
            <div id="dropdownResults" class="dropdown-results results"></div>
        </div>
        <div id="mainResults" class="main-results" style="margin-top: 20px;"></div>
    </div>

    <script>
        <?php include 'data.php'; ?>
        const data = <?php echo json_encode($data); ?>;

        const searchInput = document.getElementById('searchInput');
        const dropdownResults = document.getElementById('dropdownResults');
        const mainResults = document.getElementById('mainResults');

        function updateResults(content, showDropdown = true) {
            if (showDropdown) {
                dropdownResults.innerHTML = content;
                dropdownResults.style.display = 'block';
            }
            mainResults.innerHTML = content;
            mainResults.style.display = 'block';
        }

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            let results = [];

            if (searchTerm === '') {
                dropdownResults.style.display = 'none';
                mainResults.style.display = 'none';
                return;
            }

            // Search in both list names and sublists
            let foundItems = new Set();

            data.lists.forEach(list => {
                // Match list names
                if (list.name.toLowerCase().includes(searchTerm)) {
                    results.push(`<div class="list-item" data-type="list">${list.name}</div>`);
                }

                // Match sublists
                list.sublists.forEach(subitem => {
                    if (subitem.toLowerCase().includes(searchTerm) && !foundItems.has(subitem)) {
                        foundItems.add(subitem);
                        const parentLists = data.lists
                            .filter(l => l.sublists.includes(subitem))
                            .map(l => l.name)
                            .join(', ');
                        const highlightedText = subitem.replace(new RegExp(searchTerm, 'gi'), match => `<span class="highlight">${match}</span>`);
                        results.push(`<div class="list-item" data-type="sublist">${highlightedText}</div>`);
                    }
                });
            });

            if (results.length > 0) {
                dropdownResults.innerHTML = results.join('');
                dropdownResults.style.display = 'block';
                mainResults.innerHTML = results.join('');
                mainResults.style.display = 'block';
            } else {
                dropdownResults.style.display = 'none';
                mainResults.innerHTML = 'No results found';
            }
        });

        // Close dropdown only when clicking outside and not on a result item
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                dropdownResults.style.display = 'none';
            }
        });

        // Handle option selection
        document.addEventListener('click', function(e) {
            const item = e.target.closest('.list-item');
            if (!item || item.style.fontWeight === 'bold') return;

            const selectedText = item.textContent.split(' [')[0];
            searchInput.value = selectedText;
            searchInput.focus();

            // Hide dropdown and clear its content
            dropdownResults.style.display = 'none';
            dropdownResults.innerHTML = '';
            dropdownResults.innerHTML = '';
            mainResults.style.display = 'block';

            if (item.dataset.type === 'list') {
                // Show all sublists for the selected list
                const selectedList = data.lists.find(list => list.name === selectedText);
                if (selectedList) {
                    results = [
                        `<div class="list-item" style="font-weight: bold">Features in "${selectedText}":</div>`,
                        ...selectedList.sublists.map(subitem => 
                            `<div class="list-item" data-type="sublist">${subitem}</div>`
                        )
                    ];
                    dropdownResults.style.display = 'none';
                    mainResults.innerHTML = results.join('');
                    mainResults.style.display = 'block';
                }
            } else if (item.dataset.type === 'sublist') {
                // Hide dropdown for sublist selection
                dropdownResults.style.display = 'none';
                // Show all lists containing the selected sublist
                const selectedTextClean = selectedText.trim();
                const parentLists = data.lists.filter(list => list.sublists.includes(selectedTextClean));
                results = [
                    `<div class="list-item" style="font-weight: bold">${selectedTextClean} available in:</div>`,
                    ...parentLists.map(list => `<div class="list-item" data-type="list">${list.name}</div>`)
                ];
                mainResults.innerHTML = results.join('');
            }
        });


    </script>
</body>
</html>