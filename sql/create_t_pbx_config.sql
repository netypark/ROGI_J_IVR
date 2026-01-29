-- ============================================================================
-- T_PBX_CONFIG Table Creation
-- Date: 2026-01-30
-- Version: 1.1.0
-- Description: IP 기반 PBX 장비 설정 테이블 (이중화 지원)
--
-- v1.1.0 변경사항:
-- - return_prefix 컬럼 삭제 (모든 크로스콜이 crosscall_prefix 사용)
-- ============================================================================

-- Drop existing table if needed (주석 해제하여 사용)
-- DROP TABLE IF EXISTS T_PBX_CONFIG;

CREATE TABLE IF NOT EXISTS T_PBX_CONFIG (
    seq INT AUTO_INCREMENT PRIMARY KEY,
    pbx_id INT NOT NULL COMMENT '장비 그룹 ID (1=A장비, 2=B장비, 3=C장비)',
    pbx_name VARCHAR(50) COMMENT '장비 이름',
    server_ip VARCHAR(15) NOT NULL COMMENT '서버 IP 주소',
    server_name VARCHAR(50) COMMENT '서버 이름 (Primary/Secondary)',
    crosscall_prefix VARCHAR(10) NOT NULL COMMENT '크로스콜 prefix',
    q_length INT DEFAULT 3 COMMENT 'Q번호 자릿수',
    origin_q_length INT DEFAULT 3 COMMENT 'Origin Q번호 자릿수',
    is_active CHAR(1) DEFAULT 'Y' COMMENT '활성화 여부',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,

    UNIQUE KEY uk_server_ip (server_ip),
    INDEX idx_pbx_id (pbx_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='PBX 장비 설정 (IP 기반 이중화 지원)';

-- ============================================================================
-- 초기 데이터 입력
-- ============================================================================

-- 기존 데이터 삭제 (필요시)
-- DELETE FROM T_PBX_CONFIG;

-- A장비 그룹 (pbx_id=1)
INSERT INTO T_PBX_CONFIG (pbx_id, pbx_name, server_ip, server_name, crosscall_prefix) VALUES
(1, 'A장비', '121.254.239.45', 'A장비-Primary',   '90'),
(1, 'A장비', '121.254.239.46', 'A장비-Secondary', '90');

-- B장비 그룹 (pbx_id=2)
INSERT INTO T_PBX_CONFIG (pbx_id, pbx_name, server_ip, server_name, crosscall_prefix) VALUES
(2, 'B장비', '121.254.239.50', 'B장비-Primary',   '91'),
(2, 'B장비', '121.254.239.51', 'B장비-Secondary', '91');

-- C장비 그룹 (pbx_id=3)
INSERT INTO T_PBX_CONFIG (pbx_id, pbx_name, server_ip, server_name, crosscall_prefix) VALUES
(3, 'C장비', '121.254.239.61', 'C장비-Primary',   '92'),
(3, 'C장비', '121.254.239.62', 'C장비-Secondary', '92');

-- ============================================================================
-- 테스트용 로컬 IP 추가 (개발 환경)
-- ============================================================================
INSERT INTO T_PBX_CONFIG (pbx_id, pbx_name, server_ip, server_name, crosscall_prefix) VALUES
(1, 'A장비', '127.0.0.1', 'Localhost-Dev', '90')
ON DUPLICATE KEY UPDATE pbx_id = 1, crosscall_prefix = '90';

-- ============================================================================
-- 검증 쿼리
-- ============================================================================
SELECT '=== T_PBX_CONFIG ===' AS '';
SELECT seq, pbx_id, pbx_name, server_ip, server_name, crosscall_prefix, is_active
FROM T_PBX_CONFIG
ORDER BY pbx_id, seq;

-- ============================================================================
-- 유용한 조회 쿼리
-- ============================================================================

-- 특정 IP로 자기 장비 정보 조회
-- SELECT pbx_id, pbx_name, crosscall_prefix
-- FROM T_PBX_CONFIG
-- WHERE server_ip = '121.254.239.45' AND is_active = 'Y';

-- 특정 pbx_id의 crosscall prefix 조회 (다른 장비로 전송 시)
-- SELECT crosscall_prefix
-- FROM T_PBX_CONFIG
-- WHERE pbx_id = 2 AND is_active = 'Y'
-- LIMIT 1;
