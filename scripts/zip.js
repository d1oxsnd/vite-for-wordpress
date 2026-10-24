import fs from 'node:fs';
import path from 'node:path';
import { ZipArchive } from 'archiver';

const rootDir = process.cwd();
const themeName = path.basename(rootDir);
const zipFileName = `${themeName}.zip`;
const outputPath = path.resolve(rootDir, zipFileName);

// Удаляем старый zip если есть
if (fs.existsSync(outputPath)) {
  fs.unlinkSync(outputPath);
}

const output = fs.createWriteStream(outputPath);
const archive = new ZipArchive({
  zlib: { level: 9 }, // Максимальное сжатие
});

output.on('close', () => {
  const bytes = archive.pointer();
  const sizeKb = (bytes / 1024).toFixed(1);
  const sizeMb = (bytes / (1024 * 1024)).toFixed(2);
  const displaySize = bytes > 1024 * 1024 ? `${sizeMb} MB` : `${sizeKb} KB`;

  console.log(`\n🎉 Тема успешно упакована в ZIP!`);
  console.log(`📦 Файл:   ${zipFileName}`);
  console.log(`⚖️  Размер: ${displaySize}`);
  console.log(`📁 Путь:   ${outputPath}`);
  console.log(`✨ Готово к установке в WordPress: Внешний вид -> Темы -> Добавить тему -> Загрузить тему.\n`);
});

archive.on('error', (err) => {
  throw err;
});

archive.pipe(output);

// Исключаемые из продакшен-архива файлы разработки
const excludes = [
  'node_modules/**',
  'src/**',
  'scripts/**',
  '.git/**',
  '.github/**',
  '.vscode/**',
  '.idea/**',
  'package.json',
  'package-lock.json',
  'tsconfig.json',
  'vite.config.ts',
  '.gitignore',
  '*.zip',
  '.DS_Store',
  'Thumbs.db',
  '*.log',
];

// Упаковываем файлы внутрь папки темы
archive.glob('**/*', {
  cwd: rootDir,
  ignore: excludes,
  dot: false,
}, {
  prefix: themeName,
});

archive.finalize();
