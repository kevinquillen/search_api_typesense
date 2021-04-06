# Quick Start

1. Make sure you have a Typesense server running
2. Enable the module
3. Create a new Search API Typesense server at

        /admin/config/search/search-api/add-server

4. Configure the server credentials at

        /admin/config/search/search-api/server/{server_name}/edit

5. Create a new index on the server and add fields at

        /admin/config/search/search-api/add-index

6. Configure the Typesense Schema processor at

        /admin/config/search/search-api/index/{index_name}/processors

7. Index content from the UI or cli

**There are sure to be bugs :)**
