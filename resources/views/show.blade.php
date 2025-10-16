<html>
    <head>
        <title>Show Page</title>
    </head>
    <body>
        <h1>Profiles </h1>
        <table class="table" border="1">
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Email</th>
            </tr>
                <tr>
                    <td>{{ $profile->id  }}</td>
                    <td>{{ $profile->name }}</td>
                    <td>{{ $profile->email }}</td>
                </tr>
         
        </table>

    </body>
</html>

