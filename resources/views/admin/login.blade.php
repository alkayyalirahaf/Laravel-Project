
    <h2>Login</h2>

    @if($errors->any())
        <div style="color: red;">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('login.submit') }}" method="POST">
        @csrf
        <label>Email:</label><br>
        <input type="email" name="email" value="{{ old('email') }}" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>
