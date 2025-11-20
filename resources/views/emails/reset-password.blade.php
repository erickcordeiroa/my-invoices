<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - My Invoices</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #4F46E5; padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                My Invoices
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Conteúdo -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 24px;">
                                Olá, {{ $user->name }}!
                            </h2>
                            
                            <p style="margin: 0 0 20px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Você solicitou a redefinição de senha da sua conta no <strong>My Invoices</strong>.
                            </p>
                            
                            <p style="margin: 0 0 30px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Para redefinir sua senha, clique no botão abaixo:
                            </p>
                            
                            <!-- Botão de Redefinição -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{ url('api/v1/reset-password?email=' . urlencode($user->email) . '&token=' . $token) }}" 
                                           style="display: inline-block; padding: 15px 40px; background-color: #4F46E5; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">
                                            Redefinir Senha
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 30px 0 0 0; color: #999999; font-size: 14px; line-height: 1.6;">
                                Ou copie e cole o link abaixo no seu navegador:
                            </p>
                            
                            <p style="margin: 10px 0 0 0; padding: 15px; background-color: #f8f8f8; border-radius: 5px; word-break: break-all; font-size: 13px; color: #666666;">
                                {{ url('api/v1/reset-password?email=' . urlencode($user->email) . '&token=' . $token) }}
                            </p>
                            
                            <p style="margin: 20px 0 0 0; color: #999999; font-size: 14px; line-height: 1.6;">
                                <strong>Token de redefinição:</strong> {{ $token }}
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f8f8; padding: 20px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; color: #999999; font-size: 12px;">
                                Este link de redefinição expirará em 60 minutos.
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                Se você não solicitou a redefinição de senha, ignore este email.
                            </p>
                            <p style="margin: 15px 0 0 0; color: #999999; font-size: 12px;">
                                © {{ date('Y') }} My Invoices. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>