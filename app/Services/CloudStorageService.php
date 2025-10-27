<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CloudStorageException;

/**
 * Service pour gérer le stockage et la récupération des données depuis le cloud
 */
class CloudStorageService
{
    protected $disk;
    protected $bucket;

    public function __construct()
    {
        $this->disk = config('filesystems.cloud_disk', 's3');
        $this->bucket = config('filesystems.disks.s3.bucket');
    }

    /**
     * Récupère les données des comptes épargne archivés depuis le cloud
     *
     * @param array $filters
     * @return array
     * @throws CloudStorageException
     */
    public function getArchivedEpargneComptes(array $filters = []): array
    {
        try {
            $filePath = 'archives/comptes-epargne/data.json';

            if (!Storage::disk($this->disk)->exists($filePath)) {
                throw new CloudStorageException('Fichier d\'archives non trouvé dans le cloud');
            }

            $content = Storage::disk($this->disk)->get($filePath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new CloudStorageException('Erreur de décodage des données JSON');
            }

            return $this->filterAndSortData($data, $filters);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des comptes archivés', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            throw new CloudStorageException('Erreur lors de la récupération des données cloud: ' . $e->getMessage());
        }
    }

    /**
     * Filtre et trie les données selon les paramètres
     *
     * @param array $data
     * @param array $filters
     * @return array
     */
    protected function filterAndSortData(array $data, array $filters): array
    {
        $filteredData = $data;

        // Filtrage par recherche
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $filteredData = array_filter($filteredData, function ($item) use ($search) {
                return stripos(strtolower($item['titulaire']), $search) !== false ||
                       stripos(strtolower($item['numeroCompte']), $search) !== false;
            });
        }

        // Tri
        $sortBy = $filters['sort'] ?? 'dateCreation';
        $order = $filters['order'] ?? 'desc';

        usort($filteredData, function ($a, $b) use ($sortBy, $order) {
            $valueA = $this->getSortValue($a, $sortBy);
            $valueB = $this->getSortValue($b, $sortBy);

            if ($order === 'asc') {
                return $valueA <=> $valueB;
            } else {
                return $valueB <=> $valueA;
            }
        });

        return $filteredData;
    }

    /**
     * Obtient la valeur de tri pour un élément
     *
     * @param array $item
     * @param string $sortBy
     * @return mixed
     */
    protected function getSortValue(array $item, string $sortBy)
    {
        switch ($sortBy) {
            case 'solde':
                return $item['solde'] ?? 0;
            case 'titulaire':
                return strtolower($item['titulaire'] ?? '');
            case 'dateCreation':
            default:
                return strtotime($item['dateCreation'] ?? '1970-01-01');
        }
    }

    /**
     * Vérifie la connectivité avec le service cloud
     *
     * @return bool
     */
    public function checkConnectivity(): bool
    {
        try {
            Storage::disk($this->disk)->files('test');
            return true;
        } catch (\Exception $e) {
            Log::warning('Problème de connectivité cloud', ['error' => $e->getMessage()]);
            return false;
        }
    }
}