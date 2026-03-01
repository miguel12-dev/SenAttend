"""
Script para generar íconos PWA faltantes desde íconos existentes
Requiere: pip install Pillow
"""

from PIL import Image
import os

# Ruta base de los íconos
ICONS_PATH = os.path.join('public', 'assets', 'icons')

# Configuración de íconos a generar
# formato: (archivo_fuente, tamaño_salida, nombre_salida)
ICONS_TO_GENERATE = [
    ('web-app-manifest-192x192.png', 144, 'icon-144x144.png'),
    ('web-app-manifest-192x192.png', 128, 'icon-128x128.png'),
    ('favicon-96x96.png', 72, 'icon-72x72.png'),
    ('favicon-96x96.png', 48, 'icon-48x48.png'),
]

def generate_icon(source_file, target_size, output_file):
    """Genera un ícono redimensionado"""
    source_path = os.path.join(ICONS_PATH, source_file)
    output_path = os.path.join(ICONS_PATH, output_file)
    
    try:
        # Abrir imagen fuente
        with Image.open(source_path) as img:
            # Convertir a RGBA si no lo está
            if img.mode != 'RGBA':
                img = img.convert('RGBA')
            
            # Redimensionar con alta calidad
            resized = img.resize((target_size, target_size), Image.Resampling.LANCZOS)
            
            # Guardar
            resized.save(output_path, 'PNG', optimize=True)
            print(f'✅ Generado: {output_file} ({target_size}x{target_size})')
            return True
            
    except FileNotFoundError:
        print(f'❌ Error: No se encontró {source_file}')
        return False
    except Exception as e:
        print(f'❌ Error generando {output_file}: {str(e)}')
        return False

def main():
    print('🎨 Generador de Íconos PWA - SENAttend\n')
    
    # Verificar que existe la carpeta de íconos
    if not os.path.exists(ICONS_PATH):
        print(f'❌ Error: No se encuentra la carpeta {ICONS_PATH}')
        print('   Asegúrate de ejecutar este script desde la raíz del proyecto.')
        return
    
    # Generar cada ícono
    success_count = 0
    for source, size, output in ICONS_TO_GENERATE:
        if generate_icon(source, size, output):
            success_count += 1
    
    print(f'\n✨ Completado: {success_count}/{len(ICONS_TO_GENERATE)} íconos generados')
    
    if success_count == len(ICONS_TO_GENERATE):
        print('\n📝 Nota: Actualiza el manifest.json para incluir los nuevos íconos.')
    else:
        print('\n⚠️ Algunos íconos no se pudieron generar. Revisa los errores arriba.')

if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print('\n\n⏹️ Operación cancelada por el usuario.')
    except Exception as e:
        print(f'\n❌ Error inesperado: {str(e)}')
