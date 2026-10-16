<h1>Nova mensagem pelo site Administrar</h1>

<p><strong>Nome:</strong> {{ $name }}</p>
<p><strong>E-mail para resposta:</strong> {{ $email }}</p>
<p><strong>Assunto:</strong> {{ $messageSubject }}</p>

<p><strong>Mensagem:</strong></p>
<p>{!! nl2br(e($messageBody)) !!}</p>
