<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nuevo lead web</title>
</head>
<body style="margin:0;padding:0;background-color:#F7F7FA;font-family:Arial,Helvetica,sans-serif;color:#21172F;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F7FA;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background-color:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #E6E3EC;">
                    <tr>
                        <td style="background-color:#21172F;padding:20px 24px;">
                            <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#9B77C7;">DTW · Nuevo lead web</p>
                            <h1 style="margin:6px 0 0;font-size:20px;color:#FFFFFF;">
                                {{ $quote->company ?: $quote->client_name }}
                            </h1>
                            <p style="margin:6px 0 0;font-size:13px;color:#C9C3D4;">
                                Folio {{ $quote->quote_code }} · {{ $quote->created_at?->format('d/m/Y H:i') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
                                <tr>
                                    <td style="padding:6px 0;color:#6B6478;width:170px;">Contacto</td>
                                    <td style="padding:6px 0;"><strong>{{ $quote->client_name }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#6B6478;">Correo</td>
                                    <td style="padding:6px 0;">
                                        <a href="mailto:{{ $quote->client_email }}" style="color:#7952A5;">{{ $quote->client_email }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#6B6478;">Teléfono / WhatsApp</td>
                                    <td style="padding:6px 0;">
                                        @if ($quote->client_phone)
                                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $quote->client_phone) }}" style="color:#7952A5;">{{ $quote->client_phone }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#6B6478;">Empresa</td>
                                    <td style="padding:6px 0;">{{ $quote->company ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#6B6478;">Giro</td>
                                    <td style="padding:6px 0;">{{ $quote->industry ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#6B6478;">Empleados</td>
                                    <td style="padding:6px 0;">{{ $quote->employees ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#6B6478;">Idioma del sitio</td>
                                    <td style="padding:6px 0;">{{ strtoupper($quote->locale ?: 'es') }}</td>
                                </tr>
                            </table>

                            <hr style="border:0;border-top:1px solid #EDEAF2;margin:20px 0;">

                            <h2 style="margin:0 0 8px;font-size:15px;color:#21172F;">Problema descrito</h2>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#3B3348;white-space:pre-line;">{{ $quote->description }}</p>

                            @if (!empty($calculator))
                                <hr style="border:0;border-top:1px solid #EDEAF2;margin:20px 0;">

                                <h2 style="margin:0 0 12px;font-size:15px;color:#21172F;">Resultado de la calculadora</h2>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
                                    <tr>
                                        <td style="padding:6px 0;color:#6B6478;width:260px;">Escenario</td>
                                        <td style="padding:6px 0;">{{ $calculator['scenario_label'] ?? ($calculator['scenario'] ?? '—') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#6B6478;">Costo mensual del problema</td>
                                        <td style="padding:6px 0;">${{ number_format((float) ($calculator['monthly_cost'] ?? 0), 2) }} MXN</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#6B6478;">Monto recuperable al mes</td>
                                        <td style="padding:6px 0;color:#16A36A;"><strong>${{ number_format((float) ($calculator['recoverable'] ?? 0), 2) }} MXN</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#6B6478;">Ahorro potencial anual</td>
                                        <td style="padding:6px 0;">${{ number_format((float) ($calculator['annual_saving'] ?? 0), 2) }} MXN</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#6B6478;">Mejora estimada</td>
                                        <td style="padding:6px 0;">{{ number_format((float) ($calculator['improvement'] ?? 0), 1) }}%</td>
                                    </tr>
                                </table>

                                @if (!empty($calculator['inputs']) && is_array($calculator['inputs']))
                                    <h3 style="margin:16px 0 6px;font-size:13px;color:#6B6478;text-transform:uppercase;letter-spacing:1px;">Datos capturados</h3>
                                    <ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.7;color:#3B3348;">
                                        @foreach ($calculator['inputs'] as $key => $value)
                                            <li>{{ $key }}: {{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif

                            <p style="margin:24px 0 0;">
                                <a href="{{ route('quotes.show', $quote) }}"
                                   style="display:inline-block;background-color:#7952A5;color:#FFFFFF;text-decoration:none;font-size:14px;padding:12px 20px;border-radius:8px;">
                                    Ver la solicitud en el panel
                                </a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#F7F7FA;padding:16px 24px;font-size:12px;color:#8A8395;">
                            Este aviso se generó automáticamente desde el formulario de la landing de DTW.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

