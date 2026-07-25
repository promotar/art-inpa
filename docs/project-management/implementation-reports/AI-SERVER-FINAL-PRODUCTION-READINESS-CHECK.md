# AI Server Final Production Readiness Check

## df -h بعد التوسعة

```text
Filesystem      Size  Used Avail Use% Mounted on
/dev/sda1       232G  104G  129G  45% /
```

## docker ps

```text
CONTAINER ID   IMAGE                        COMMAND                  CREATED          STATUS              PORTS                       NAMES
1cbf2cad6210   ai-server-ai-gateway         "uvicorn app.main:ap…"   23 minutes ago   Up About a minute   10.10.0.40:8080->8080/tcp   ai-gateway
032fc8ec8b4e   ai-server-embedding-worker   "uvicorn app.main:ap…"   23 minutes ago   Up About a minute   8092/tcp                    ai-embedding-worker
1bd3ab9473a8   ai-server-vision-worker      "uvicorn app.main:ap…"   23 minutes ago   Up About a minute   8091/tcp                    ai-vision-worker
b84aa6542908   ai-server-comfyui            "/opt/nvidia/nvidia_…"   4 hours ago      Up About a minute   8188/tcp                    ai-comfyui
314850c2ae64   qdrant/qdrant:latest         "./entrypoint.sh"        21 hours ago     Up About a minute   6333-6334/tcp               ai-qdrant
f8ac1748e228   ollama/ollama:latest         "/bin/ollama serve"      21 hours ago     Up About a minute   11434/tcp                   ai-ollama
```

## docker compose ps

```text
NAME                  IMAGE                        COMMAND                  SERVICE            CREATED          STATUS          PORTS
ai-comfyui            ai-server-comfyui            "/opt/nvidia/nvidia_…"   comfyui            4 hours ago      Up 13 seconds   8188/tcp
ai-embedding-worker   ai-server-embedding-worker   "uvicorn app.main:ap…"   embedding-worker   21 minutes ago   Up 21 seconds   8092/tcp
ai-gateway            ai-server-ai-gateway         "uvicorn app.main:ap…"   ai-gateway         21 minutes ago   Up 22 seconds   10.10.0.40:8080->8080/tcp
ai-ollama             ollama/ollama:latest         "/bin/ollama serve"      ollama             21 hours ago     Up 23 seconds   11434/tcp
ai-qdrant             qdrant/qdrant:latest         "./entrypoint.sh"        qdrant             21 hours ago     Up 23 seconds   6333-6334/tcp
ai-vision-worker      ai-server-vision-worker      "uvicorn app.main:ap…"   vision-worker      21 minutes ago   Up 22 seconds   8091/tcp
```

## /health result

```json
{
  "ok": true,
  "gateway": true,
  "ollama": true,
  "comfyui": true,
  "qdrant": true,
  "vision_worker": true,
  "embedding_worker": true,
  "embedding_model": "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
  "production_semantic": true
}
```

## /models result

```json
{
  "ok": true,
  "data": {
    "general": {
      "endpoint": "general",
      "model": "qwen3:8b",
      "backend": "ollama"
    },
    "coding": {
      "endpoint": "coding",
      "model": "qwen2.5-coder:7b-instruct",
      "backend": "ollama"
    },
    "vision": {
      "endpoint": "vision",
      "model": "Qwen/Qwen2.5-VL-7B-Instruct",
      "backend": "vision-worker",
      "mode": "lazy/interface"
    },
    "image": {
      "endpoint": "image",
      "model": "stabilityai/stable-diffusion-xl-base-1.0",
      "backend": "comfyui"
    },
    "image_fast": {
      "endpoint": "image_fast",
      "model": "ByteDance/SDXL-Lightning",
      "backend": "comfyui"
    },
    "artwork_similarity": {
      "endpoint": "artwork_similarity",
      "model": "clip-ViT-B-32",
      "backend": "qdrant + embedding-worker",
      "status": "interface-prepared"
    },
    "text_embedding": {
      "endpoint": "text_embedding",
      "model": "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
      "backend": "embedding-worker + qdrant"
    }
  }
}
```

## اختبار توليد صورة سريع

```json
{
  "start_ok": true,
  "final": {
    "ok": true,
    "job_id": "2578cd1d-308b-45d6-be7e-8e0560a235ee",
    "status": "completed",
    "result_image": "http://10.10.0.40:8080/generated/art_inpa_sdxl_lightning_00005_.png",
    "error": null
  },
  "poll_url": "http://10.10.0.40:8080/v1/images/jobs/2578cd1d-308b-45d6-be7e-8e0560a235ee"
}
```

## اختبار RAG index/search

```json
{
  "index_ok": true,
  "indexed": 2,
  "embedding_model": "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
  "production_semantic": true,
  "search_ok": true,
  "result_count": 3,
  "top_score": 0.7197921,
  "top_document_id": "final-readiness-rag-20260629"
}
```

## اختبار vision analyze

```json
{
  "ok": true,
  "semantic": true,
  "model": "llava:7b",
  "image_used": "http://10.10.0.40:8080/generated/art_inpa_sdxl_lightning_00005_.png"
}
```

## اختبار artwork similarity

```json
{
  "index_ok": true,
  "artwork_id": "final-readiness-artwork-20260629",
  "collection": "artwork_vectors_clip_v1",
  "embedding_model": "clip-ViT-B-32",
  "dimensions": 512,
  "production_semantic": true,
  "search_ok": true,
  "result_count": 2,
  "top_score": 1.0
}
```

## تأكيد أن API key لم يتم طباعته

```text
Confirmed: API key was read only inside the server-side test script and was not printed in terminal output or this report.
```

## تأكيد أن backup موجود

```json
{
  "/root/codex-backups/ai-server-production-hardening-20260628-204930": true
}
```

## تأكيد أن الخدمات تعمل بعد reboot/restart

```json
{
  "vm_was_booted_after_disk_expansion": true,
  "docker_compose_restart_executed": true,
  "health_after_restart": {
    "ok": true,
    "gateway": true,
    "ollama": true,
    "comfyui": true,
    "qdrant": true,
    "vision_worker": true,
    "embedding_worker": true,
    "embedding_model": "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
    "production_semantic": true
  }
}
```
