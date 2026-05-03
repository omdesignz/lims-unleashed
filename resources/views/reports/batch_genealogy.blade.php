<h3>Batch History: {{ $batch->batch_number }}</h3>
<table width="100%" border="1" cellpadding="5">
    <thead>
        <tr>
            <th>Date</th>
            <th>User</th>
            <th>Action</th>
            <th>Qty Change</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($batch->transactions as $tx)
        <tr>
            <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $tx->user->name }}</td>
            <td>{{ $tx->type->name }}</td>
            <td>{{ $tx->qty }}</td>
            <td>{{ $tx->reason }}</td>
        </tr>
        @endforeach
    </tbody>
</table>